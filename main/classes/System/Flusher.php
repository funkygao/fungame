<?php

namespace System;

final class Flusher {

    /**
     * @var \Model\Base\Flushable[]
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

    public static function register(\Model\Base\Flushable $flushable) {
        self::getInstance()->_register($flushable);
    }

    private function _register(\Model\Base\Flushable $flushable) {
        $this->_subscribers[] = $flushable;
    }

    public function flushAll() {
        foreach ($this->_subscribers as $flushable) {
            if ($flushable->isDirty()) {
                $flushable->save();
            }
        }
    }

    public function getSubscribers() {
        return $this->_subscribers;
    }

}
