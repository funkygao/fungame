<?php

namespace System;

final class Queue {

    private $_instantMsgList = array();
    private $_msgList = array();

    public static function instance() {
        static $instance = NULL;
        if (NULL === $instance) {
            $instance = new self();
        }

        return $instance;
    }

    /**
     * Append to msg list for MQ.
     *
     * Because MQ worker and php process are separated processes, they
     * need timing sync for a given data because we use db write buffer.
     *
     * Otherwise MQ worker may not be able to get the fresh data if worker
     * get msg before php flushes write buffer.
     *
     * @param string $tube
     * @param array $data
     * @param array $channels
     */
    public function appendMsg($tube, array $params, array $channels) {
        if ($channels) {
            $this->_msgList[] = array(
                'tube' => $tube,
                'params' => $params,
                'channels' => $channels,
            );
        }
    }

    public function appendInstantMsg(callable $callback, array $params) {
        $this->_instantMsgList[] = array(
            'callback' => $callback,
            'params' => $params,
        );
    }

    public function getMsgList() {
        return $this->_msgList;
    }

    public function getInstantMsgList() {
        return $this->_instantMsgList;
    }

}
