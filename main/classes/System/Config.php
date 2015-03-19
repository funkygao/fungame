<?php

namespace System;

final class Config {

    public static function get($name, $keyPath = '', $default = NULL) {
        static $files = array();
        if (!isset($files[$name])) {
            $file = DATA_PATH . 'config' . DIRECTORY_SEPARATOR . $name . '.php';
            if (!file_exists($file)) {
                return $default;
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

    /**
     * Put kingdom under maintenance by: global | kingdom.
     *
     * @param null|int $kingdomId Null if query global maintenance duration
     * @return int 0 means not under maintenance, else in minute
     */
    public static function maintenanceDuration($kingdomId = NULL) {
        $maintainConfig = self::get('maintain', 'maintain_mode', array());
        if (!empty($maintainConfig['global'])) {
            return $maintainConfig['global'];
        }

        if (!is_null($kingdomId) && !empty($maintainConfig["kingdom_$kingdomId"])) {
            return $maintainConfig["kingdom_$kingdomId"];
        }

        return 0;
    }

}
