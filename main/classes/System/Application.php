<?php

namespace System;

/**
 * The execution engine.
 *
 * It is responsible for the dispatch|routing and response rendering.
 */
final class Application
    implements \Consts\LoggerConst, \Consts\AppConst {

    /**
     * @var ResponseHandler
     */
    private static $_response;

    /**
     * @var RequestHandler
     */
    private static $_request;

    /**
     * @var Logger
     */
    private static $_logger;

    /**
     * @var Application
     */
    private static $_instance = NULL;

    public static function getInstance(RequestHandler $request, ResponseHandler $response) {
        if (NULL === self::$_instance) {
            self::$_instance = new self($request, $response);
        }

        return self::$_instance;
    }

    private function __construct(RequestHandler $request, ResponseHandler $response) {
        self::$_response = $response;
        self::$_request = $request;
    }

    public final function __clone() {
        throw new \Exception('You can not clone a singleton.');
    }

    public function init() {
        ini_set('display_errors', 'Off');
        error_reporting(E_ALL^E_NOTICE); // in php.ini when release

        // register logger first
        foreach (\System\Config::get('log') as $logger) {
            \System\Appender\Factory::register($logger);
        }
        self::$_logger = \System\Logger::getLogger(__CLASS__);

        // let these unexpected handlers registered as soon as possible
        register_shutdown_function(array(__CLASS__, 'shutdown_handler'));
        set_exception_handler(array(__CLASS__, 'exception_handler'));
        set_error_handler(array(__CLASS__, 'error_handler'));

        self::$_request->validate();
        self::$_request->setupRequestOpTime();

        return $this;
    }

    public static function shutdown_handler() {
        if ($error = error_get_last()) {
            self::exception_handler(new \ErrorException($error['message'],
                $error['type'], 0, $error['file'], $error['line']));
        }
    }

    public static function error_handler($code, $error, $file = NULL, $line = NULL) {
        if (E_ERROR & $code) {
            // fatal run-time errors, e,g., mem alloc failure
            $ex = new \ErrorException($error, $code, 0, $file, $line);
            self::$_logger->exception($ex);
            
            // let client know we got into trouble
            self::$_instance->_setExceptionResponsePayload($ex)->printResponse();
        } else if (error_reporting() & $code) {
            static $errcodeMapping = array(
                2 => 'E_WARNING',
                4 => 'E_PARSE',
                8 => 'E_NOTICE',
            );
            if (!empty($errcodeMapping[$code])) {
                $code = $errcodeMapping[$code];
            }

            $message = date('[m-d H:i:s] ')
                . $code . ': '
                . $error . '  '
                . '- ' . $file . ' [' . $line . ']'
                . PHP_EOL;
            error_log($message, 3, self::PHP_ERROR_FILE); // 3 means append to logfile
            // log the backtrace, TODO remove on production env
            $ex = new \Exception();
            error_log($ex->getTraceAsString(), 3, self::PHP_ERROR_FILE);
        }

        return TRUE;
    }

    public static function exception_handler(\Exception $ex) {
        self::$_logger->exception($ex);

        self::$_instance->_setExceptionResponsePayload($ex)->printResponse();
        return TRUE;
    }

    private function _setExceptionResponsePayload(\Exception $ex) {
        /*
         * only biz related exceptions will be rendered to client
         * system related exceptions are invisible to client
         */
        $msg = self::MSG_SYS_ERROR;
        if (TRUE || $ex instanceof \System\GameException) { // FIXME before release
            $msg = $ex->getMessage();
        }
        $msg = $ex->getMessage() . ' -- ' . $ex->getTraceAsString();

        self::$_response->fail()
            ->setMessage($msg);
        return self::$_response;
    }

    /**
     * @param string $controllerClass
     * @param string $method
     * @return \Services\Base\BaseService
     * @throws HttpNotFoundException
     */
    public static function buildController($controllerClass, $method = '') {
        if (!$controllerClass) {
            throw new HttpNotFoundException("Empty controller classname");
        }

        $controllerClass = ucfirst($controllerClass) . 'Service';
        $controllerClass = "Services\\$controllerClass";
        if (!class_exists($controllerClass)) {
            throw new HttpNotFoundException("Class does not exist: $controllerClass");
        }

        $controller = $controllerClass::getInstance(self::$_request, self::$_response);
        if ($method && !method_exists($controller, $method)) {
            throw new HttpNotFoundException("Invalid method: $controllerClass->$method");
        }

        return $controller;
    }

    public function execute() {
        ob_start();

        // dispatch request to target action, execute the api routine
        try {
            $action = self::$_request->request('method');
            $params = self::$_request->getParams();

            self::$_logger->debug(self::CATEGORY_REQUEST, array(
                "request" => array(
                    "class" => self::$_request->request("class"),
                    "method" => self::$_request->request("method"),
                    "params" => $params,
                ),
            ));

            $startedAt = time();
            // TODO only CallService is permitted here
            $controller = self::buildController(self::$_request->request('class'), $action);
            // hook, wakeup pending jobs
            // we can't call JobModel::wakeupPendingJobs() because of batch mechanism
            // for batch, each op will wakeup job for opTime
            $controller->beforeAction($params);
            $result = $controller->{$action}($params);

            // flush dirty rows to db
            \System\Flusher::getInstance()->flushAll();

            if (time() - $startedAt > self::THRESHOLD_SLOW_RESPONSE) {
                self::$_logger->warn(self::CATEGORY_SLOWREQUEST, array(
                    'call' => self::$_request->actionName(),
                    'params' => $params,
                ));
            }

            self::$_response->succeed()
                ->setPayload($result);

            self::$_logger->debug(self::CATEGORY_RESPONSE, array(
                    'call' => self::$_request->actionName(),
                    'result' => $result,
                )
            );
        } catch (HttpNotFoundException $ex) {
            self::$_response->notFound();
        } catch (\Exception $ex) {
            self::$_logger->exception($ex);
            $this->_setExceptionResponsePayload($ex);
        }

        self::$_response->printResponse();
    }

}

class HttpNotFoundException extends \Exception {}
