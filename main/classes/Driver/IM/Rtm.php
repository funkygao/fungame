<?php

namespace Driver\IM;

final class Rtm implements Driver, \Consts\LoggerConst, \Consts\ErrnoConst {

    const MESSAGE_TYPE_GENERAL = 100;

    private static $_cli;

    public function init() {
        require_once(V2LIB_PATH . 'Rtm/vendor/autoload.php');
    }

    private function connectIfNot() {
        if (!self::$_cli) {
            $this->connect();
        }
    }

    private function connect() {
        $config = \System\Config::get('rtm');
        $primaryHosts = array_deep_get($config, 'primary_hosts', array('dispatch.test.rtm.infra.funplus.net:13031',));
        $backupHosts = array_deep_get($config, 'backup_hosts', array());
        $timeout = array_deep_get($config, 'timeout', 1000);
        $projectId = array_deep_get($config, 'project_id', 10001);
        $appSecretKey = array_deep_get($config, 'secret_key', 'test_key');

        $pool = new \Funplus\Rtm\Pool(
            $primaryHosts,
            $backupHosts,
            $timeout
        );

        self::$_cli = new \Funplus\Rtm\Gate($pool->client, $projectId, $appSecretKey);
    }

    /**
     * @param string $channel
     * @param string $cmd
     * @param array $payload
     * @param array $data
     * @param int $flag 0:user, 1:group, 2:all(when $flag is 2, $channel will be ignored)
     * @param int $sender who sends the message
     * @return bool
     * @throws \Funplus\Thrift\serverGated_serverGatedException | \ExpectedErrorException
     */
    public function publish($channel, $cmd, array $payload, array $data = null, $flag = 0, $sender = 0) {
        $this->connectIfNot();
        $msg = json_encode($this->_pack($cmd, $payload, $data));
        switch($flag) {
            case 0:
                self::$_cli->message->send(self::MESSAGE_TYPE_GENERAL, $sender, $channel, $msg);
                break;
            case 1:
                self::$_cli->message->sendGroup(self::MESSAGE_TYPE_GENERAL, $sender, $channel, $msg);
                break;
            case 2:
                self::$_cli->message->sendAll(self::MESSAGE_TYPE_GENERAL, $sender, $msg);
                break;
            default:
                throw new \ExpectedErrorException("RTM publish flag mismatch: {$flag}", self::ERRNO_EXCEPTION_RTM);
        }

        return TRUE;
    }

    /**
     * @param int $uid ID of current user
     * @return int ID of the chat room
     * @throws \Funplus\Thrift\serverGated_serverGatedException | \ExpectedErrorException
     */
    public function getToken($userChatChannel) {
        $this->connectIfNot();
        $ret = self::$_cli->token->get($userChatChannel);
        if ($ret instanceof \Funplus\Thrift\serverGated_Token && $ret->auth_token) {
            return $ret->auth_token;
        }
        $this->_getLogger()->error(self::CATEGORY_RTM, array(
            'msg' => "get token error",
            'data' => $ret,
        ));
        throw new \ExpectedErrorException("Error occurred when get rtm token: user_channel[$userChatChannel]", self::ERRNO_EXCEPTION_RTM);
    }

    /**
     * @return int ID of the chat room
     * @throws \Funplus\Thrift\serverGated_serverGatedException | \ExpectedErrorException
     */
    public function createOrJoinChatRoom($groupId, $userChatChannel) {
        $this->connectIfNot();
        if (!is_int($groupId)) {
            throw new \ExpectedErrorException("Chat room id should be int, {$groupId} given", self::ERRNO_EXCEPTION_RTM);
        }
        return self::$_cli->friend->joinGroup($groupId, $userChatChannel, TRUE);
    }

    public function joinChatRoom($groupId, $userChatChannel) {
        $this->connectIfNot();
        return self::$_cli->friend->joinGroup($groupId, array($userChatChannel));
    }

    public function leaveChatRoom($groupId, $userChatChannel) {
        $this->connectIfNot();
        return self::$_cli->friend->leaveGroup($groupId, array($userChatChannel));
    }

    public function deleteChatRoom($groupId) {
        $this->connectIfNot();
        return self::$_cli->group->del($groupId);
    }

    protected function _pack($cmd, $payload, $data = null) {
        $msg = array();
        $msg['cmd'] = $cmd;
        $msg['payload'] = $payload;
        $msg['data'] = $data;
        $msg['time'] = \System\RequestHandler::getInstance()->currentOpTime(); // so that client(subscriber) knows the sequence order
        return $msg;
    }

    private function _getLogger() {
        return \System\Logger::getLogger(get_class($this));
    }
}
