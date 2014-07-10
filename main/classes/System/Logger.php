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
        $this->_log($category, $msg);
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
        $this->_log($category, $msg);
    }

    public function warn($category, $msg) {
        $this->_log($category, $msg);
    }

    public function error($category, $msg) {
        $this->_log($category, $msg);
    }

    public function exception(\Exception $ex) {
        // category is fixed
        $this->_log(self::CATEGORY_EXCEPTION, $this->_getExceptionLogMessage($ex));
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
        $this->_log($category, $msg);

        throw $ex; // stop the world
    }

    private function _getExceptionLogMessage(\Exception $ex) {
        return array(
            'cls' => get_class($ex),
            'msg' => $ex->getMessage(),
            'file' => $ex->getFile(), // TODO strip basedir
            'line' => $ex->getLine(),
            'trace' => $ex->getTrace(),
            'request' => \System\RequestHandler::getInstance()->request(),
        );
    }

    /**
     * Invoke System\Appender\Factory::register BEFORE this.
     *
     * @param string $category
     * @param array|string $msg
     */
    private function _log($category, $msg) {
        if (is_array($msg)) {
            $msg['_ctx'] = request_ctx();
            $request = \System\RequestHandler::getInstance();
            $msg['_ctx']['action'] = $request->actionName();
            $cmds = $request->request('params.cmds');
            if ($cmds) {
                $msg['_ctx']['cmds'] = $cmds;
            }
            $msg = json_encode($msg);
        }

        self::$_handler->append($category, $msg);
    }

}
