<?php

namespace Service;

/**
 * Services entry point and batch commands dispatcher.
 */
final class CallService extends Base\BaseService
    implements \Consts\LoggerConst, \Consts\JobConst {

    private function _getCommonReply() {
        return array(
            self::REPLY_SERVER_TIME => time(), // server time for mobile client to sync clock
        );
    }

    /**
     * The handshake, load player all data at once.
     *
     * Register new user if not found.
     *
     * @In('{"lang":"zh-Hanz","os":"OSXEditor:0000","mobile_id":"709BB0A3-C331-544D-AB9A-C161A1D06AEC"}')
     *
     * @todo prevent from register flood
     * @param array $params
     * @return array
     */
    public function init($params) {
        $mobileId = $params['mobile_id']; // TODO rename to mid
        $userManager = \Manager\UserManager::getInstance();
        $logger = $this->_getLogger();

        // create or load the uid
        $uid = \Model\Lookup\UserLookupModel::deviceId2Uid($mobileId);
        if (!$uid) {
            $uid = $userManager->signupUser($mobileId);
            $logger->info(self::CATEGORY_INIT, array(
                'type' => 'singup',
                'uid' => $uid,
                'device' => $mobileId,
                'os' => $params['os'],
            ));
        }

        // got the current user
        $params['uid'] = $uid;
        parent::beforeAction($params);

        // session related
        $reply = array();
        $reply['token'] = $userManager->issueSessionToken($uid);

        // start to fill in the payload

        // basic user info
        $payload = array();
        $payload['user'] = $userManager->loadUserInfo($uid);
        $payload['alliance'] = \Manager\AllianceManager::getInstance()
            ->loadUserAllianceData($uid);
        if ($payload['alliance']) {
            $payload['alliance']['gifts'] = \Manager\GiftManager::getInstance()
                ->getAllianceGifts($uid);
        }
        $payload['city_ids'] = $userManager->getUserCityIds($uid);
        $activeCityId = $payload['city_ids'][0]; // FIXME the first is the active city
        $payload['city'] = $userManager->loadUserCity($uid, $activeCityId);
        $payload['hero'] = $userManager->loadHero($uid, $activeCityId);
        $payload['inventories'] = $userManager->loadInventories($uid);
        $payload['consumables'] = $userManager->loadUserConsumables($uid);
        $payload['stronghold'] = $userManager->getStrongholdOverview($uid, $activeCityId);

        $payload['contacts'] = \Manager\ContactsManager::getInstance()->fetchAll($uid);
        $payload['jobs'] = \Model\JobModel::exportJobs($uid);

        // list all pubnub channels, server decides channel name
        $chatManager = \Manager\ChatManager::getInstance();
        $allianceId = \Model\UserInfoModel::get($uid)->alliance_id;
        $allianceChannel = $allianceId ? "alliance_$allianceId" : '';
        $payload['chat'] = array(
            'kingdom' => $chatManager->getInitChatKingdom($uid),
            'rooms' => $chatManager->getInitChatRooms($uid),
            'groups' => $chatManager->getInitChatGroups($uid),
            'alliance' => $allianceChannel,
            'private' => "user_$uid",
        );

        $payload['quests'] = \Manager\QuestManager::getInstance()
            ->loadQuest($uid);
        $payload['bounties'] = array(); // TODO remove it!
        $payload['stats'] = \Model\StatsModel::exportStats($uid);

        // research
        $researchModels = \Model\ResearchModel::getAll($uid);
        $researches = array();
        foreach ($researchModels as $r) {
            if ($r->job_id) {
                $job = \Model\JobModel::get($uid, $r->job_id);
                $researches[$r->research_id] = array(
                    'time_start' => $job->time_start,
                    'time_end' => $job->time_end,
                    'target_id' => $job->trace['target'],
                );
            } else {
                $researches[$r->research_id] = NULL;
            }
        }
        $payload['research'] = $researches;

        // march of pve and pvp
        $warManager = \Manager\WarManager::getInstance();
        $encounterManager = \Manager\EncounterManager::getInstance();
        $payload['pve'] = $warManager->loadUserPveMarches($uid);

        $payload['pveV2'] = array(
            'zone' => $encounterManager->exportAllEncounters($uid),
            'march' => $warManager->exportPveMarch($uid),
        );
        // TODO pvp march not implemented
        
        $payload['training'] = array();
        foreach ($userManager->getUserCityIds($uid) as $cityId) {
            $payload['training'][] = array(
                'uid' => $uid,
                'city_id' => $cityId,
                'troop' => \Manager\CityManager::getInstance()
                    ->getTrainingTroop($uid, $cityId),
            );
        }

        $logger->info(self::CATEGORY_INIT, array(
            'type' => 'login',
            'uid' => $uid,
            'device' => $mobileId,
            'os' => $params['os'],
        ));
        $this->_metricsManager->recordCallInit($uid, $params);

        $reply[self::REPLY_PAYLOAD] = $payload;
        $reply += $this->_getCommonReply();

        return $reply;
    }

    /**
     * A batch request consists of multiple API calls combined into one HTTP request.
     *
     * When server receives the batched request, it applies the request's 'cmds' parameter to
     * each part, and then treats each part as if it were a separate HTTP request.
     *
     * For DW, Unity3D will keep a queue and send out the queued actions in a single HTTP request
     * to backend in timely order.
     *
     * The queue will be fired when the one of the following conditions is satisfied:
     * <ul>
     * <li>queue is full</li>
     * <li>timeout</li>
     * <li>there comes in an instant action</li>
     * </ul>
     *
     * @uri('/call/commit')
     * @In('{'token':'xxxx', 'seq':'yyy', 'ctime': 1400573118, 'cmds':[{'op':"Player:loadPlayerInfo",'args':{'uid':3,'ver':1},'at':1399395294}]}')
     * @param array $params
     * @return array
     */
    public function commit($params) {
        $uid = $params['uid']; // TODO discard before release

        /*
         * commands = [
         *  {
         *      'op': 'xx:yy', // action name, controller:action
         *      'at': 2112122, // opTime of this action
         *      'args': {
         *      }
         *  },
         * ]
         */
        $commands = $params['cmds'];
        \System\BatchDag::getInstance()->feedCommands($commands);

        $reply = array();
        $requestHandler = $this->request();
        foreach ($commands as $cmd) {
            list($controllerClass, $action) = explode(':', $cmd['op']);
            if ($controllerClass == 'Call') { // __CLASS__ has namespace
                $this->_getLogger()->warn(self::CATEGORY_WARNING, array(
                    'msg' => 'player is hacking us into deadloop',
                ));

                continue; // skip the deadloop trap
            }

            // let upper layer handle the exceptions
            $requestHandler->setOpTime($cmd['at']);
            $controller = \System\Application::buildController($controllerClass, $action);
            $args = isset($cmd['args']) ? $cmd['args'] : array();
            $controller->beforeAction($args);
            $args['uid'] = $uid; // FIXME discard before release
            $result = $controller->{$action}($args);
            if (is_array($result) && $result) {
                // only 1 action in a batch commands can have payload result
                $reply['payload'] = $result;
            } else {
                // FIXME frontend currently requires batch payload content is hashmap
                $reply['payload']['ret'] = $result;
            }
        }

        $reply += $this->_getCommonReply();
        return $reply;
    }

}
