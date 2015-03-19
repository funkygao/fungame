<?php

namespace Driver\IM;

// Pubnub best practices: https://help.pubnub.com/entries/22271342-PubNub-Best-Practices
// TODO persistent connection with pubnub, currently it's tooooooooo slow 300ms per call
// run IMFactoryBench to understand its throughput
final class Pubnub implements Driver {

    const CLIENT_MSG_TYPE = "sys_message";

    protected $_publish_key;
    protected $_subscribe_key;
    protected $_secret_key;
    protected $_cipher_key;
    protected $_use_ssl;

    /**
     * @var \Pubnub
     */
    private static $_pubnubHandle;

    // called only once
    public function init() {
        require_once(V2LIB_PATH . 'PubNub/Pubnub.php');

        $config = \System\Config::get('pubnub');
        $this->_publish_key = array_deep_get($config, "publish_key", "demo");
        $this->_subscribe_key = array_deep_get($config, "subscribe_key", "demo");
        $this->_secret_key = array_deep_get($config, "secret_key", FALSE);
        $this->_cipher_key = array_deep_get($config, "cipher_key", FALSE);
        $this->_use_ssl = array_deep_get($config, "use_ssl", FALSE);

        self::$_pubnubHandle = new \Pubnub(
            $this->_publish_key,
            $this->_subscribe_key,
            $this->_secret_key,
            $this->_cipher_key,
            $this->_use_ssl
        );
    }

    public function publish($channel, $cmd, array $payload, array $data = null, $flag = 0, $sender = 0) {
        $result = self::$_pubnubHandle->publish(array(
            'channel' => $channel,
            'message' => $this->_pack($cmd, $payload, $data),
        )); // [1, 'Sent', 14042129827325508]

        if (is_array($result) && $result[1] == 'Sent') {
            return TRUE;
        }
        $this->_getLogger()->warn('pubnub.log', array(
            'errmsg' => $result,
        ));

        return FALSE;
    }

    protected function _pack($cmd, $payload, $data = null) {
        $msg = array();
        $msg['cmd'] = $cmd;
        $msg['payload'] = $payload;
        $msg['data'] = $data;
        $msg['time'] = \System\RequestHandler::getInstance()->currentOpTime(); // so that client(subscriber) knows the sequence order
        return $msg;
    }

    // only for testing, not used in production code
    public function conn() {
        return self::$_pubnubHandle;
    }

    private function _getLogger() {
        return \System\Logger::getLogger(get_class($this));
    }

}
