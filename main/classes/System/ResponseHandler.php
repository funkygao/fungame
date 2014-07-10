<?php

namespace System;

final class ResponseHandler {

    const
        ERROR = 0,
        SUCCESS = 1,
        MAINTENANCE = 2,
        CHEAT_DETECTED = 3; // client is responsible for handling this err, e,g. false alarm?

    const
        CONTENT_TYPE_JSON = 'application/json',
        CONTENT_TYPE_HTML = 'text/html';

    private $_status = '';
    private $_message = '';
    private $_payload = array();

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

    public function cheatDetected() {
        $this->_status = self::CHEAT_DETECTED;
        return $this;
    }

    public function notFound() {
        header('Status: 404 Not Found', TRUE, 404);
        exit;
    }

    public function underMaintenance($minute = 10) {
        // maintainance break(borrowed from ClashOfClan)
        $this->_status = self::MAINTENANCE;
        $this->setMessage($minute); // how soon player can retry
        return $this;
    }

    public function setMessage($message) {
        $this->_message = $message;
        return $this;
    }

    public function setPayload($payload) {
        $this->_payload = $payload;
        return $this;
    }

    public function printResponse() {
        $this->_printHeader();

        $this->_payload['ok'] = $this->_status;
        if ($this->_status != self::SUCCESS && $this->_message) {
            // only when not success, will we render msg to client
            $this->_payload['msg'] = $this->_message;
        }
        print @json_encode($this->_payload); // TODO msgpack, check json error
    }

    private function _printHeader() {
        // setup http response header, TODO cache header
        if (!headers_sent()) {
            $contentType = self::CONTENT_TYPE_JSON;
            header("Content-Type: $contentType");

            // cache, TODO Unity3D need this?
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
            header('Cache-Control: no-store, no-cache, must-revalidate'); // HTTP/1.1
            header('Cache-Control: post-check=0, pre-check=0', false);
            header('Pragma: no-cache');
        }
    }

}
    
