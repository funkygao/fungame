<?php

namespace System\Appender;

final class Factory implements Appender {

    const
        HANDLER_FILE = 'file',
        HANDLER_FLUENTD = 'fluentd',
        HANDLER_ALS = 'als';

    private static $_instance;

    /**
     * @var array Array of System\Appender\Appender
     */
    private static $_handlers = array();

    /**
     * Register a logger handler.
     *
     * Can be called several times.
     *
     * Variant arguments.
     */
    public static function register($loggerUrl) {
        $url = parse_url($loggerUrl);
        if ($url === false) {
            throw new \InvalidArgumentException("Invalid handler url: {$loggerUrl}");
        }
        switch ($url["scheme"]) {
            case self::HANDLER_FILE:
                self::$_handlers[] = new File($url["path"]);
                break;

            case self::HANDLER_ALS:
                self::$_handlers[] = new Als($url["path"]);
                break;

            case self::HANDLER_FLUENTD:
                self::$_handlers[] = new Fluentd();
                break;

            default:
                throw new \InvalidArgumentException("Invalid handler type: {$url["scheme"]}");
        }
    }

    /**
     * @return Appender
     * @throws \InvalidArgumentException
     */
    public static function instance() {
        if (self::$_handlers === NULL) {
            throw new \InvalidArgumentException('Call register() before this');
        }

        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function append($category, $msg) {
        foreach (self::$_handlers as $handler) {
            $handler->append($category, $msg);
        }
    }

}
