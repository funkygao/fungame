<?php

namespace System\Appender;

class Als implements Appender {

    private static $_sock = NULL;

    public function __construct($unixSocketPath) {
        if (NULL === self::$_sock) {
            if (!self::$_sock = fsockopen('unix://' . $unixSocketPath)) {
                trigger_error("ALS socket failed to open: $unixSocketPath");
            }
        }
    }

    public function append($category, $msg) {
        if (!self::$_sock) {
            return;
        }

        list($floatTime, $intTime) = explode(' ', microtime());
        $time = $intTime * 1000 + round($floatTime * 1000, 0);
        $msg = "dw,$time,$msg";
        fwrite(self::$_sock, ":$category,$msg\n");
    }

}
