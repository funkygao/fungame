<?php

namespace System;

final class Logger implements \Consts\LoggerConst {

    private static $_logger = array();

    /**
     * @var Appender\Appender
     */
    private static $_handler;

    private $_clsName;

    private function __construct($clsName, Appender\Appender $handler = NULL) {
        $this->_clsName = $clsName;
        self::$_handler = $handler;
        if (self::$_handler === NULL) {
            self::$_handler = Appender\Factory::instance();
        }
    }

    /**
     * @param string $clsName clsName
     * @param Appender\Appender $handler
     * @return Logger
     */
    public static function getLogger($clsName = '', Appender\Appender $handler = NULL) {
        if (!isset(self::$_logger[$clsName])) {
            self::$_logger[$clsName] = new self($clsName, $handler);
        }

        return self::$_logger[$clsName];
    }

    public function info($category, $msg) {
        $this->_log($category, 'INFO', $msg);
    }

    public function debug($category, $msg) {
        if (is_array($msg)) {
            $stack = debug_backtrace();
            $caller = $stack[0];
            $msg['_src'] = array(
                'file' => $caller['file'],
                'line' => $caller['line'],
            );
        }
        $this->_log($category, 'DEBUG', $msg);
    }

    public function warn($category, $msg) {
        $this->_log($category, 'WARN', $msg);
    }

    public function error($category, $msg) {
        $this->_log($category, 'ERROR', $msg);
    }

    public function exception(\Exception $ex) {
        // category is fixed
        $this->_log(self::CATEGORY_EXCEPTION, 'FATAL', $this->_getExceptionLogMessage($ex));
    }

    /**
     * @param string $event the event name
     * @param string $uid
     * @param array $properties
     */
    public function traceBI($event, $uid, array $properties) {
        // http://config.funplusgame.com/get-bi-metadata
        // http://wiki.ifunplus.cn/display/BI/payment
        // https://docs.google.com/spreadsheets/d/1ILr_Zn_Hhn_zakWJ7X8v5YbzKoniczxbSKqX58nrpXY/edit#gid=0
        $sessionId = '';
        if (isset($properties['session_id'])) {
            $sessionId = $properties['session_id'];
            unset($properties['session_id']);
        }

        $msg = array(
            'bi_version' => '1.2',
            'app_id' => 'dragon.global.prod',
            'ts' => time(),
            'event' => $event,
            'user_id' => $uid,
            'session_id' => $sessionId,
            'properties' => $properties,
        );
        $this->_log('bi', 'TRACE', json_encode($msg), FALSE);
    }

    public function panic($category, $msg, \Exception $ex = NULL) {
        if ($ex === NULL) {
            $ex = new \RuntimeException('panic');
        }

        if (!is_array($msg)) {
            $msg = array(
                'msg' => $msg,
            );
        }
        $msg['_trace'] = $this->_getExceptionLogMessage($ex);
        $this->_log($category, 'PANIC', $msg);

        throw $ex; // stop the world
    }

    private function _getExceptionLogMessage(\Exception $ex) {
        return array(
            'cls' => get_class($ex),
            'msg' => $ex->getMessage(),
            'file' => $ex->getFile(), // TODO strip basedir
            'line' => $ex->getLine(),
            'trace' => $ex->getTrace(),
            'code' => $ex->getCode(),
            'request' => \System\RequestHandler::getInstance()->request(),
        );
    }

    /**
     * Invoke System\Appender\Factory::register BEFORE this.
     *
     * @param string $category
     * @param string $level
     * @param array|string $msg
     * @param bool $addHeader
     */
    private function _log($category, $level, $msg, $addHeader = TRUE) {
        if (is_array($msg)) {
            $msg['_ctx'] = request_ctx();
            $request = \System\RequestHandler::getInstance();
            $msg['_ctx']['action'] = $request->actionName();
            $cmds = $request->request('params.cmds');
            if ($cmds) {
                $msg['_ctx']['cmds'] = $cmds;
            }
            $ua = $request->getParams()['ua'];
            if ($ua) {
                $msg['_ctx']['ua'] = $ua;
            }
            $msg = json_encode($msg);
        }

        self::$_handler->append($category, $level, $msg, $addHeader);
    }

}
