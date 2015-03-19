<?php

namespace Services;

/**
 * Services entry point and batch commands dispatcher.
 *
 * @package Services
 */
final class CallService extends Base\BaseService
    implements \Consts\LoggerConst, \Consts\JobConst,
    \Consts\AppConst, \Consts\QueueConst {

    // The init sequence of batch from client
    const INIT_SEQ = 1;

    /**
     * @var \Manager\UserManager
     */
    private $_userManager;

    /**
     * @var \Manager\StatsManager
     */
    private $_statsManager;

    protected function __construct() {
        parent::__construct();

        $this->_userManager = \Manager\UserManager::getInstance();
        $this->_statsManager = \Manager\StatsManager::getInstance();
    }

    // called before call.init
    public function manifest($params) {
        $domain = get_domain();
        $cdn = array_merge(
            \System\Config::get('global', 'cdn'),
            (array)\System\Config::get('global', 's3'),
            (array)$domain
        );
        $reply = array(
            'build' => \System\Config::get('global', 'app_build_id'),
            'cdn' => $cdn,
            'proxy_ip' => \System\Config::get('global', 'proxy_ip'), // used by FE when DNS lookup fail for game server
            'ver' => \System\GameData::getAssetsVersion(),
            'im' => \System\Config::get('global', 'im'),
        );
        return $reply;
    }

    // DiffieHellman for key exchange
    public function x($params) {
        // public key from the client
        $clientPubKey = $params['pub_key'];

        // private key: a 308 bit num
        $privateKey = gmp_strval(gmp_random(16));

        $dh = new \Crypt_DiffieHellman(self::DIFFIE_HELLMAN_PRIME,
            self::DIFFIE_HELLMAN_GENERATOR, $privateKey);
        $dh->generateKeys();
        // save the secret key with the udid
        $dhSecretKey = $dh->computeSecretKey($clientPubKey)
            ->getSharedSecretKey();

        return $dh->getPublicKey();
    }

    /**
     * So that we know game session duration in seconds.
     *
     * @In('{"duration":"100"}')
     */
    public function bye($params) {
        $duration = (int)$params['duration'];
    }

    /**
     * The handshake, load player all data at once.
     *
     * Register new user if not found.
     *
     * @In('{"lang":"zh-Hanz","os":"OSXEditor:0000","mobile_id":"709BB0A3-C331-544D-AB9A-C161A1D06AEC","mailLastSyncAt":"1415189357"}')
     *
     * @todo prevent from register flood
     * TODO rename mobile_id to udid
     * @param array $params
     * @return array
     */
    public function init($params) {
        $udid = $params['mobile_id']; // TODO rename to udid
        //$deviceToken = $params['dtoken']; // device token sent from client

        // create or load the uid
        $uid = \Model\Lookup\UserLookupModel::deviceId2Uid($udid);
        if (!$uid) {
            try {
                $uid = $this->_userManager->signupUser($udid);

                $this->_getLogger()->traceBI('new_user', $uid, array(
                    'os' => $params['os'],
                    'udid' => $udid,
                ));
            } catch (\Thrift\Exception\TException $ex) {
                // unrecoverable
                throw $ex;
            } catch (\Exception $ex) {
                // FIXME deleteCurrentUser 中只是修改了uuid到一个随机的40位字串, 避免小概率uuid冲突删掉该条数据较好
                \Model\Lookup\UserLookupModel::resetUserByUdid($udid);
                throw new \RestartGameException($ex->getMessage() . ':' . $ex->getTraceAsString());
            }

            $this->_getLogger()->info(self::CATEGORY_INIT, array(
                'type' => 'signup',
                'uid' => $uid,
                'device' => $udid,
                'os' => $params['os'],
            ));
        }

        // got the current user
        $params['uid'] = $uid;
        \System\RequestHandler::getInstance()->setUid($uid);

        $reply = array();
        $callbackData = array();
        $this->_userManager->loginReward($uid);
        $reply['user'] = $this->_userManager->loadUserInfo($uid);

        // for callback
        $callbackData[\Model\UserInfoModel::$tname][] = $this->_userManager
            ->loadUserInfoCallback($uid);
        $allianceData = \Manager\AllianceManager::getInstance()->loadUserAllianceData($uid);
        $callbackData = array_merge_recursive($callbackData, $allianceData);

        $reply['city_ids'] = $this->_userManager->getUserCityIds($uid);
        $defaultCityId = $this->_userManager->getDefaultCityId($uid);
        $cityInfo = $this->_userManager->loadUserCity($uid, $defaultCityId);
        $kingdomId = $cityInfo['info']['world_id'];
        $kingdomMaintenanceDuration = \System\Config::maintenanceDuration($kingdomId);
        if ($kingdomMaintenanceDuration) {
            throw new \MaintainException($kingdomMaintenanceDuration);
        }
        // for callback
        $city = \Model\CityModel::get($uid, $defaultCityId);
        $callbackData[\Model\CityModel::$tname][] = $city->export();
        $callbackData[\Model\CityTileModel::$tname] = \Model\CityTileModel
            ::exportAll($uid, $defaultCityId);

        $jwtToken = $this->_userManager->issueToken($uid, $kingdomId);
        $reply['token'] = $jwtToken;

        if (\Driver\IMFactory::ifUseRtm()) {
            $reply['chat_token'] = \Driver\IMFactory::instance('rtm')
                ->getToken(\Model\UserInfoModel::get($uid)->chat_channel); // FIXME rename chat_channel to im_chan
        }

        $reply['hero'] = $this->_userManager->loadHero($uid, $defaultCityId);
        // for callback
        $callbackData[\Model\HeroModel::$tname][] = \Model\HeroModel
            ::get($uid, $defaultCityId)->export();
        $reply['inventories'] = $this->_userManager->loadInventories($uid);
        $reply['consumables'] = $this->_userManager->loadUserConsumables($uid);
        // for callback
        $callbackData[\Model\InventoryModel::$tname] = \Model\InventoryModel::exportAll($uid);
        $callbackData[\Model\ConsumableModel::$tname] = \Model\ConsumableModel::exportAll($uid);

        $reply['stronghold'] = $this->_userManager->getStrongholdOverview($uid, $defaultCityId);

        // secret gifts
        $giftManager = \Manager\GiftManager::getInstance();
        $reply['secret_gifts'] = $giftManager->checkSecretGiftTime($uid);

        //contacts
        $contactsData = \Manager\ContactsManager::getInstance()->exportContacts($uid);
        $callbackData = array_merge_recursive($callbackData, $contactsData);

        //jobs
        $reply['jobs'] = \Model\JobModel::exportProgresses($uid);
        $jobs[\Model\JobModel::$tname] = \Model\JobModel::exportAll($uid);
        $callbackData = array_merge_recursive($callbackData, $jobs);

        // list all pubnub channels, server decides channel name
        $chatManager = \Manager\ChatManager::getInstance();
        $allianceId = \Model\UserInfoModel::get($uid)->alliance_id;
        $allianceChannel = $allianceId ? "alliance_$allianceId" : '';

        // chat channels
        $reply['chat'] = array(
            'kingdom' => $chatManager->getInitChatKingdom(),
            'rooms' => $chatManager->getInitChatRooms($uid),
            'groups' => $chatManager->getInitChatGroups($uid),
            'alliance' => $allianceChannel,
            'private' => "user_$uid",
        );

        $reply['quests']['empire'] = \Manager\QuestManager::getInstance()
            ->loadEmpireQuest($uid);
        $callbackData[\Model\Quest\Timer::$tname] = \Manager\QuestManager::getInstance()
            ->loadInitQuest($uid);
        $callbackData[\Model\StatsModel::$tname] = \Model\StatsModel::exportAll($uid);

        $reply['bounties'] = array(); // TODO remove it!
        $reply['stats'] = \Model\StatsModel::exportStats($uid);

        // research
        $reply['research'] = \Model\ResearchModel::exportAll($uid);
        $callbackData[\Model\ResearchModel::$tname] = \Model\ResearchModel::exportAll($uid);

        // mail
        $reply['mail'] = \Manager\MailManager::getInstance()
            ->getList($uid, intval($params['mailLastSyncAt']));

        // march of pve and pvp
        $warManager = \Manager\WarManager::getInstance();
        $chapterManager = \Manager\ChapterManager::getInstance();
        $reply['pve'] = array(
            'chapter' => $chapterManager->exportAllChapters($uid),
            'march' => \Manager\PVEManager::getInstance()->exportMarch($uid),
        );
        $reply['pvp'] = $warManager->exportPvpMarches($uid);

        $reply['training'] = array();
        foreach ($this->_userManager->getUserCityIds($uid) as $cityId) {
            $reply['training'][] = array(
                'uid' => $uid,
                'city_id' => $cityId,
                'troop' => \Manager\CityManager::getInstance()
                    ->getTrainingTroop($uid, $cityId),
            );
        }

        // rally info
        if ($allianceId) {
            $rallyData = \Manager\RallyManager::getInstance()
                ->getRallyList($allianceId, $uid);
            $callbackData = array_merge_recursive($callbackData, $rallyData);
        }

        //bookmark info
        $callbackData[\Model\BookmarkModel::$tname]=\Model\BookmarkModel::exportAll($uid);

        //chat room
        $userRooms = \Model\Chat\UserChatRoomModel::getAll($uid);
        foreach($userRooms as $userRoom) {
            $callbackData[\Model\Chat\ChatRoomModel::$tname][] = 
                \Model\Chat\ChatRoomModel::get($userRoom->room_id)->export(array('mtime'));
            $chatRoomRosters = \Model\Chat\ChatRoomRosterModel::getAll($userRoom->room_id);
            foreach ($chatRoomRosters as $chatRoomRoster) {
                $callbackData[\Model\Chat\ChatRoomRosterModel::$tname][] = $chatRoomRoster->export(array('mtime'));
            }
        }

        $worldMap = \Model\WorldMapModel::get($kingdomId);
        $callbackData[\Model\WorldMapModel::$tname][] = $worldMap->export();

        // login stats
        $this->_statsManager->updateUserLoginStats($uid, $this->_currentOpTime());
        $reply['consecutive_login_days'] = \Model\UserProfileModel::get($uid)->consecutive_login_days;

        // user benefit
        $callbackData[\Model\UserBenefitModel::$tname][] = \Manager\PropertyManager::getInstance($uid, $defaultCityId)
            ->loadUserBenefits();

        // friends online status
        $reply['friends_online_status'] = \Manager\ContactsManager::getInstance()
            ->getFriendsOnlineStatus($uid);

        $this->_getLogger()->info(self::CATEGORY_INIT, array(
            'type' => 'login',
            'uid' => $uid,
            'device' => $udid,
            'os' => $params['os'],
        ));
        $this->_getLogger()->traceBI('session_start', $uid, array(
            'os' => $params['os'],
            'session_id' => $jwtToken,
        ));

        // seq nonce reset and clear last result cache
        $requestHandler = \System\RequestHandler::getInstance();
        $redis = \Driver\RedisFactory::instance();
        $seqKey = $requestHandler->getSeqRedisKey();
        $lastResultKey = $requestHandler->getLastResultRedisKey();
        $redis->set($seqKey, self::INIT_SEQ);
        $redis->set($lastResultKey, '');

        // for callback
        $reply['data'] = $callbackData;

        // notify my friends I'm back!
        \System\Queue::instance()->appendMsg(self::TUBE_USER,
            array(
                'cmd' => 'online',
                'payload' => array(
                    'uid' => $uid,
                ),
            ),
            array_map(function ($userId) {
                return \Model\UserInfoModel::get($userId)->chat_channel;
            }, \Manager\ContactsManager::getInstance()->getFriendUids($uid)));

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
        list($controllerClass, $action) = explode(':', $params['op']);
        if ($controllerClass == 'Call') { // __CLASS__ has namespace
            $this->_getLogger()->warn(self::CATEGORY_WARNING, array(
                'msg' => 'player is hacking us into deadloop',
                'uid' => $params['uid'],
            ));
            return null;
        }

        // let upper layer handle the exceptions
        $controller = \System\Application::buildController($controllerClass, $action);
        $args = isset($params['args']) ? $params['args'] : array();
        if (!isset($args['uid'])) {
            $args['uid'] = $params['uid']; // FIXME discard before release
        }
        if (isset($args['uid'])) {
            \System\RequestHandler::getInstance()->setUid($args['uid']);
        }
        return $controller->{$action}($args);
    }

}
