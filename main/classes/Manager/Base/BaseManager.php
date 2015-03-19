<?php

namespace Manager\Base;

abstract class BaseManager {

    /**
     * @return BaseManager
     */
    public static function getInstance(/* arg1, arg2, ... */) {
        static $instances = array();
        $clsName = get_called_class();
        $args = func_get_args();
        if (count($args) == 0) {
            if (!isset($instances[$clsName])) {
                $instances[$clsName] = new $clsName();
            }

            return $instances[$clsName];
        }

        // has arguments pass in, child must have 'public function __construct($arg1, $arg2)' defined
        $key = json_encode($args);
        if (!isset($instances[$clsName][$key])) {
            $reflect = new \ReflectionClass($clsName);
            $instances[$clsName][$key] = $reflect->newInstanceArgs($args);
        }

        return $instances[$clsName][$key];
    }

    public final function __clone() {
        throw new \Exception('You can not clone a singleton.');
    }

    protected final function _getLogger() {
        return \System\Logger::getLogger(get_class($this));
    }

}
