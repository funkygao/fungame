<?php

namespace System;

final class Config {

    public static function get($name, $keyPath = '', $default = NULL) {
        static $files = array();
        if (!isset($files[$name])) {
            $file = DATA_PATH . 'config' . DIRECTORY_SEPARATOR . $name . '.php';
            if (!file_exists($file)) {
                throw new \InvalidArgumentException("Cannot find config file: $file");
            }

            $files[$name] = require_once $file;
        }

        if (!$keyPath) {
            // the whole array
            return $files[$name];
        }

        return array_deep_get($files[$name], $keyPath, $default);
    }

    public static function isDebugMode() {
        return self::get('global', 'debug', FALSE);
    }

}
