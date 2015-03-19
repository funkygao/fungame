<?php

namespace System;

/**
 * The execution engine.
 *
 * It is responsible for the dispatch|routing and response rendering.
 */
final class Application
    implements \Consts\LoggerConst, \Consts\AppConst, \Consts\ErrnoConst {

    /**
     * @var ResponseHandler
     */
    private static $_responseHandler;

    /**
     * @var RequestHandler
     */
    private static $_requestHandler;

    /**
     * @var Logger
     */
    private static $_logger;

    public function __construct(\System\RequestHandler $request,
                                \System\ResponseHandler $response) {
        self::$_requestHandler = $request;
        self::$_responseHandler = $response;
    }

    public function init() {
        date_default_timezone_set('UTC'); // because our mysql use UTC
        ini_set('display_errors', 'Off');
        error_reporting(E_ALL^E_NOTICE); // in php.ini when release

        // register logger first
        foreach (\System\Config::get('global', 'logger') as $logger) {
            \System\Appender\Factory::register($logger);
        }
        self::$_logger = \System\Logger::getLogger(__CLASS__);

        // let these unexpected handlers registered as soon as possible
        register_shutdown_function(array($this, 'shutdown_handler'));
        set_exception_handler(array($this, 'exception_handler'));
        set_error_handler(array($this, 'error_handler'));

        self::$_requestHandler->validate();
        self::$_requestHandler->setupRequestOpTime();

        return $this;
    }

    public function shutdown_handler() {
        // TODO maybe we need \System\LockStep::releaseAll(); here
        if ($error = error_get_last()) {
            $this->exception_handler(new \ErrorException($error['message'],
                $error['type'], 0, $error['file'], $error['line']));
        }
    }

    public function error_handler($code, $error, $file = NULL, $line = NULL) {
        if (E_ERROR & $code) {
            // fatal run-time errors, e,g., mem alloc failure
            $ex = new \ErrorException($error, $code, 0, $file, $line);
            self::$_logger->exception($ex);

            // let client know we got into trouble
            $this->_setExceptionResponsePayload($ex)->printResponse();
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
            error_log($ex->getTraceAsString() . PHP_EOL, 3, self::PHP_ERROR_FILE);
        }

        return TRUE;
    }

    public function exception_handler(\Exception $ex) {
        self::$_logger->exception($ex);

        $this->_setExceptionResponsePayload($ex)->printResponse();
        return TRUE;
    }

    private function _setExceptionResponsePayload(\Exception $ex, $code = NULL) {
        /*
         * only biz related exceptions will be rendered to client
         * system related exceptions are invisible to client
         */
        $msg = self::MSG_SYS_ERROR;
        if (TRUE || $ex instanceof \RestartGameException) { // FIXME before release
            $msg = $ex->getMessage();
        }

        if (NULL === $code) {
            $code = $ex->getCode();
        }

        // TODO return err code to client instead of err msg
        self::$_responseHandler->fail()
            ->setMessage($msg)
            ->setCode($code);

        if ($ex instanceof \RestartGameException) {
            self::$_responseHandler->restartGame();
        }

        return self::$_responseHandler;
    }

    /**
     * @param string $controllerClass
     * @param string $method
     * @return \Services\Base\BaseService
     * @throws \HttpNotFoundException
     */
    public static function buildController($controllerClass, $method = '') {
        if (!$controllerClass) {
            throw new \HttpNotFoundException("Empty controller classname");
        }

        $controllerClass = ucfirst($controllerClass) . 'Service';
        $controllerClass = "Services\\$controllerClass";
        if (!class_exists($controllerClass)) {
            throw new \HttpNotFoundException("Class does not exist: $controllerClass");
        }

        $controller = $controllerClass::getInstance(self::$_requestHandler, self::$_responseHandler);
        if ($method && !method_exists($controller, $method)) {
            throw new \HttpNotFoundException("Invalid method: $controllerClass->$method");
        }

        return $controller;
    }

    public function execute() {
        try {
            $globalMaintenanceDuration = \System\Config::maintenanceDuration();
            if ($globalMaintenanceDuration) {
                throw new \MaintainException($globalMaintenanceDuration);
            }

            $class = self::$_requestHandler->request('class');
            $action = self::$_requestHandler->request('method');
            if (API_VER != 'v1') {
                // backwards compatibility
                $suffix = str_replace('.', '_', API_VER); // '.' not allowed in func name
                // CallService::init_v1_1 (if API_VER = v1.1)
                //$action .= '_' . $suffix; // TODO turn this on
            }
            $params = self::$_requestHandler->getParams();

            self::$_logger->debug(self::CATEGORY_REQUEST, array(
                'request' => array(
                    'class' => $class,
                    'method' => $action,
                    'params' => $params,
                ),
            ));

            if (self::$_requestHandler->needToken()) {
                // FIXME token时，uid是靠UserManager deocde JWT的，这里却直接getUid，奇怪
                $uid = self::$_requestHandler->getUid();
            }
            if (!isset($params['uid'])) {
                $params['uid'] = $uid; // FIXME discard before release
            }

            $controller = self::buildController($class, $action);
            $maxLockRetry = 3;
            for ($retries = 0; $retries < $maxLockRetry; $retries++) {
                try {
                    $result = $controller->{$action}($params);
                } catch (\LockException $ex) {
                    $waitMs = ($maxLockRetry - $retries) * 50 + rand(10, 50);
                    usleep(1000 * $waitMs);
                    self::$_logger->warn('lock', array(
                        'retry' => $retries + 1,
                        'uid' => $ex->getMessage(),
                        'wait' => $waitMs,
                    ));

                    continue;
                }

                // lucky, didn't encounter lock exception
                break;
            }
            if ($retries == $maxLockRetry) {
                // we have to give up retry
                throw $ex;
            }

            if (self::$_requestHandler->request('race_mode')) {
                // 前端传入这个参数，来强制后端模拟并发、数据竞争场景
                // TODO kill it before release
                sleep(rand(5, 10));
            }

            $flushTime = \System\Flusher::getInstance()->flushAll(); // may throw PDOException
            self::$_responseHandler->setCallbackTime($flushTime);
            \Driver\DbFactory::instance()->commitAll();

            // trace request/response
            $elapsed = round(microtime(TRUE) - REQUEST_TIME_FLOAT, 3);
            self::$_logger->debug(self::CATEGORY_RESPONSE, array(
                    'size' => is_array($result) ? strlen(json_encode($result)) : 1,
                    'elapsed' => $elapsed,
                    'result' => $result,
                )
            );
            if ($elapsed > self::THRESHOLD_SLOW_RESPONSE) {
                self::$_logger->warn(self::CATEGORY_SLOWREQUEST, array(
                    'call' => self::$_requestHandler->actionName(),
                    'params' => $params,
                ));
            }

            if (self::$_requestHandler->isTokenlessService($class)) {
                self::$_responseHandler->succeed()
                    ->setPayload($result, TRUE);
            } else {
                self::$_responseHandler->succeed()
                    ->setPayload($result);
            }
        } catch (\PDOException $ex) { // mysql driver exception, never happens if fae
            // will lead to db data inconsistency because of db write buffer
            // some system use MQ to implement internal rollback mechanism
            // out log 'IS' MQ for eventual consistency
            // TODO we need more robust and auto rollback mechanism
            \Driver\DbFactory::instance()->rollbackAll();

            self::$_logger->error(self::CATEGORY_REDOLOG, array(
                'err' => $ex->getMessage(),
                'trace' => $ex->getTrace(),
                'req' => self::$_requestHandler->export(),
                'redo' => \Model\Base\Table::getRedoLog(),
            ));

            self::$_logger->exception($ex);
            \Model\Base\ActiveRecord::undoCreates();
            $this->_setExceptionResponsePayload($ex, self::ERRNO_EXCEPTION_PDO);
        } catch (\RedisException $ex) { // redis driver exception, never happens if fae
            \Driver\DbFactory::instance()->rollbackAll();

            self::$_logger->error(self::CATEGORY_REDOLOG, array(
                'err' => $ex->getMessage(),
                'trace' => $ex->getTrace(),
                'req' => self::$_requestHandler->export(),
                'redo' => \Model\Base\Table::getRedoLog(),
            ));

            self::$_logger->exception($ex);
            \Model\Base\ActiveRecord::undoCreates();
            $this->_setExceptionResponsePayload($ex, self::ERRNO_EXCEPTION_REDIS);
        } catch (\MaintainException $ex) {
            // 系统挂维护, ex.message 表示多少分钟后用户可以重试
            self::$_responseHandler->underMaintenance($ex->getMessage());
            \Model\Base\ActiveRecord::undoCreates();
        } catch (\HttpNotFoundException $ex) { // invalid request url
            \Driver\DbFactory::instance()->rollbackAll();

            self::$_responseHandler->notFound();
            \Model\Base\ActiveRecord::undoCreates();
        } catch (\LockException $ex) {
            self::$_logger->warn('lock', array(
                'accquire' => 'fail',
                'uid' => $ex->getMessage(),
            ));

            self::$_responseHandler->locked();
            \Model\Base\ActiveRecord::undoCreates();
        } catch (\ExpectedErrorException $ex) {
            \Driver\DbFactory::instance()->rollbackAll();

            self::$_logger->exception($ex);
            \Model\Base\ActiveRecord::undoCreates();
            self::$_responseHandler->fail()
                ->setPayload($ex->getPayload());
        } catch (\RestartGameException $ex) {
            \Driver\DbFactory::instance()->rollbackAll();

            if ($ex instanceof \CheatingException) {
                // TODO log cheat
            }

            self::$_logger->exception($ex);
            \Model\Base\ActiveRecord::undoCreates();
            self::$_responseHandler->restartGame()
                ->setMessage($ex->getMessage()); // FIXME hide this in prod env
        } catch (\OptimisticLockException $ex) { // TODO kill this
            \Driver\DbFactory::instance()->rollbackAll();

            self::$_logger->exception($ex);
            \Model\Base\ActiveRecord::undoCreates();
            $this->_setExceptionResponsePayload($ex, self::ERRNO_EXCEPTION_OPTIMISTICLOCK);
        } catch (\Funplus\Thrift\serverGated_serverGatedException $ex) {
            self::$_logger->exception($ex);
            \Model\Base\ActiveRecord::undoCreates();

            self::$_responseHandler->restartGame()
                ->setMessage($ex->getMessage());
        } catch (\Thrift\Exception\TTransportException $ex) { // fae连接问题
            self::$_logger->exception($ex);

            \Model\Base\ActiveRecord::undoCreates();

            self::$_responseHandler->restartGame()
                ->setMessage($ex->getMessage());

            // reconnect to fae
            \FaeEngine::reopenTransport();
        } catch (\Thrift\Exception\TApplicationException $ex) { // fae返回的业务异常
            self::$_logger->exception($ex);

            \Model\Base\ActiveRecord::undoCreates();

            if (str_endswith($ex->getMessage(), 'entity being locked')) {
                // TODO maintain mode, this user is being migrated
                // TODO test this
                self::$_responseHandler->underMaintenance($ex->getMessage());
            } else if (str_endswith($ex->getMessage(), 'circuit open')) {
                // 无计可施，挂维护 FIXME test it
                self::$_responseHandler->underMaintenance($ex->getMessage());
            } else {
                self::$_responseHandler->restartGame()
                    ->setMessage($ex->getMessage());
            }
        } catch (\Exception $ex) {
            \Driver\DbFactory::instance()->rollbackAll();

            self::$_logger->exception($ex);

            \Model\Base\ActiveRecord::undoCreates();

            $this->_setExceptionResponsePayload($ex, self::ERRNO_EXCEPTION_UNKNOWN);
        }

        \System\LockStep::releaseAll(); // might throw exception FIXME

        $payloadSize = self::$_responseHandler->printResponse();

        if (\FaeEngine::isConnected() && $this->_shouldReportLatency($class, $action)) {
            \System\GameEngine::instance()->reportRequestSummary($payloadSize);
        }

    }

    private function _shouldReportLatency($class, $method) {
        $ignoredClasses = array(
            'debug' => TRUE,
            'tools' => TRUE,
        );
        $ignoredMethods = array(
            'ping' => TRUE,
            'manifest' => TRUE,
        );
        
        if (isset($ignoredClasses[strtolower($class)]) || isset($ignoredMethods[$method])) {
            return FALSE;
        }

        return TRUE;
    }

}
