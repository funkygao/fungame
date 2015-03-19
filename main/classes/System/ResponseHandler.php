<?php

namespace System;

final class ResponseHandler implements \Consts\ErrnoConst {

    const
        ERROR = 0, // expected game error
        SUCCESS = 1,
        MAINTENANCE = 2,
        RESTART_GAME = 3, // restart game
        LOCKED = 4; // player get freezed, client need retry after some time

    const
        CONTENT_TYPE_JSON = 'application/json',
        CONTENT_TYPE_HTML = 'text/html';

    private $_status = '';
    private $_message = '';
    private $_code = 0; // errno
    private $_time;
    private $_payload = array();
    private $_autoCallbackData = array();

    public static function getInstance() {
        static $instance = NULL;
        if (NULL === $instance) {
            $instance = new self();
        }

        return $instance;
    }

    private function __construct() { }

    public final function __clone() {
        throw new \Exception('You can not clone a singleton.');
    }

    public function succeed() {
        $this->_status = self::SUCCESS;
        return $this;
    }

    public function fail() {
        $this->_status = self::ERROR;
        return $this;
    }

    public function locked() {
        $this->_status = self::LOCKED;
        return $this;
    }

    public function getStatus() {
        return $this->_status;
    }

    public function isStatusSuccess() {
        return $this->_status == self::SUCCESS;
    }

    public function isStatusError() {
        return $this->_status == self::ERROR;
    }

    public function underMaintenance($minute = 10) {
        // maintainance break(borrowed from ClashOfClan)
        $this->_status = self::MAINTENANCE;
        $this->setMessage($minute); // how soon player can retry
        return $this;
    }

    public function restartGame() {
        $this->_status = self::RESTART_GAME;
        return $this;
    }

    public function notFound() {
        header('Status: 404 Not Found', TRUE, 404);
        exit;
    }

    public function setMessage($message) {
        $this->_message = $message;
        return $this;
    }

    public function setCode($code) {
        $this->_code = $code;
        return $this;
    }

    public function setCallbackTime($time) {
        $this->_time = $time;
    }

    public function setPayload($payload, $directReturn = FALSE) {
        if ($directReturn) {
            $this->_payload = $payload;
            return $this;
        }

        $this->_payload['payload'] = array();
        if (!is_array($payload)) {
            $this->_payload['payload']['ret'] = $payload;
        } else {
            foreach ($payload as $key => $value) {
                if ((string)$key == 'data') { // FIXME why type convert?
                    $this->_payload['data'] = array_deep_filter($value);
                } else {
                    $this->_payload['payload'][$key] = $value;
                }
            }
        }

        if ($this->_status == self::SUCCESS
            && !isset($payload['data'])
            && !empty($this->_autoCallbackData)) {
            $this->_payload['data'] = $this->_autoCallbackData;
        }

        $this->_payload['time'] = isset($this->_time)?
            $this->_time : (int) (microtime(TRUE) * 1000);

        return $this;
    }

    public function getAutoCallback() {
        return $this->_autoCallbackData;
    }

    public function setAutoCallback($data) {
        $this->_autoCallbackData = $data;
    }

    public function appendAutoCallback(array $callbackData) {
        foreach ($callbackData as $op => $table) {
            if ($op != 'set' && $op != 'del') {
                throw new \ExpectedErrorException("Invalid callback op key: $op", self::ERRNO_SYS_INVALID_ARGUMENT);
            }

            $tname = key($table);
            $this->_autoCallbackData[$op][$tname][] = current($table);
        }
    }

    /**
     * @return int Payload size in bytes
     */
    public function printResponse() {
        $this->printHeader();

        // payload maybe not a array, e.g. 'TRUE'
        if (is_array($this->_payload)) {
            $this->_payload['ok'] = $this->_status;
        }

        if ($this->_status != self::SUCCESS && $this->_message) {
            if ($this->_code) {
                $this->_payload['code'] = $this->_code;
            }
            $this->_payload['msg'] = $this->_message; // TODO kill this
        }

        $result = @json_encode($this->_payload); // TODO msgpack, check json error
        print $result;

        // FIXME: redis maybe throw exception, but not catch
        $requestHandler = \System\RequestHandler::getInstance();
        if ($requestHandler->isCommit() &&
            ($this->isStatusSuccess() || $this->isStatusError())) {
            $seqKey = $requestHandler->getSeqRedisKey();
            $lastResultKey = $requestHandler->getLastResultRedisKey();
            $redis = \Driver\RedisFactory::instance();
            $redis->incr($seqKey);
            $redis->set($lastResultKey, $result);
        }
        return strlen($result);
    }

    public function printHeader() {
        if (!headers_sent()) {
            header('Content-Type: ' . self::CONTENT_TYPE_JSON);
        }
    }

}
    
