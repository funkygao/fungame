<?php

namespace System;

final class Flusher {

    /**
     * @var Flushable[]
     */
    private $_subscribers = array();

    public static function getInstance() {
        static $instance = NULL;
        if ($instance === NULL) {
            $instance = new self();
        }

        return $instance;
    }

    private function __construct() { }

    public static function register(Flushable $flushable) {
        self::getInstance()->_register($flushable);
    }

    private function _register(Flushable $flushable) {
        $this->_subscribers[] = $flushable;
    }

    public function flushAll() {

        // 1. flush rtm control messages
        foreach (\System\Queue::instance()->getInstantMsgList() as $msg) {
            call_user_func_array($msg['callback'], $msg['params']);
        }

        // 2. flush all flushables
        foreach ($this->_subscribers as $flushable) {
            if ($flushable->isDirty()) {
                $flushable->save();
            }
        }

        $flushTime = (int) (microtime(TRUE) * 1000);

        // 3. it's now time to flush queue
        foreach (\System\Queue::instance()->getMsgList() as $msg) {
            // pass in current user opTime to MQ worker
            $msg['params']['time'] = $flushTime;

            if (!isset($msg['params']['data'])) {
                $msg['params']['data'] = \System\ResponseHandler::getInstance()->getAutoCallback();
            }
            if (isset($msg['params']['from'])) {
                $from = $msg['params']['from'];
            } else {
                $from = \Model\UserInfoModel::get(\System\RequestHandler::getInstance()->getUid())->chat_channel;
            }
            if (!\Driver\MQFactory::instance($msg['tube'])
                ->produce($msg['params'], $msg['channels'], $from)) {
                // failed to put to MQ
            }
        }

        return $flushTime;
    }

    public function getSubscribers() {
        return $this->_subscribers;
    }

}
