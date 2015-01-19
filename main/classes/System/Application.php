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
        $faeClientBroken = FALSE; // fae如果抛出异常，则这个连接就断了

        try {
            $globalMaintenanceDuration = \System\Config::maintenanceDuration();
            if ($globalMaintenanceDuration) {
                throw new \MaintainException($globalMaintenanceDuration);
            }

            $class = self::$_requestHandler->request('class');
            $action = self::$_requestHandler->request('method');
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
        } catch (\PDOException $ex) {
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

            \Model\Base\ActiveRecord::undoCreates();
            self::$_logger->exception($ex);
            $this->_setExceptionResponsePayload($ex, self::ERRNO_EXCEPTION_PDO);
        } catch (\RedisException $ex) {
            \Driver\DbFactory::instance()->rollbackAll();

            self::$_logger->error(self::CATEGORY_REDOLOG, array(
                'err' => $ex->getMessage(),
                'trace' => $ex->getTrace(),
                'req' => self::$_requestHandler->export(),
                'redo' => \Model\Base\Table::getRedoLog(),
            ));

            \Model\Base\ActiveRecord::undoCreates();
            self::$_logger->exception($ex);
            $this->_setExceptionResponsePayload($ex, self::ERRNO_EXCEPTION_REDIS);
        } catch (\MaintainException $ex) {
            // 系统挂维护, ex.message 表示多少分钟后用户可以重试
            \Model\Base\ActiveRecord::undoCreates();
            self::$_responseHandler->underMaintenance($ex->getMessage());
        } catch (\ShardLockException $ex) {
            \Model\Base\ActiveRecord::undoCreates();
            self::$_responseHandler->underMaintenance(60 * 24);
        } catch (\HttpNotFoundException $ex) {
            \Driver\DbFactory::instance()->rollbackAll();

            \Model\Base\ActiveRecord::undoCreates();
            self::$_responseHandler->notFound();
        } catch (\LockException $ex) {
            self::$_logger->warn('lock', array(
                'accquire' => 'fail',
                'uid' => $ex->getMessage(),
            ));

            \Model\Base\ActiveRecord::undoCreates();
            self::$_responseHandler->locked();
        } catch (\ExpectedErrorException $ex) {
            \Driver\DbFactory::instance()->rollbackAll();

            \Model\Base\ActiveRecord::undoCreates();
            self::$_logger->exception($ex);
            self::$_responseHandler->fail()
                ->setPayload($ex->getPayload());
        } catch (\RestartGameException $ex) {
            \Driver\DbFactory::instance()->rollbackAll();

            if ($ex instanceof \CheatingException) {
                // TODO log cheat
            }

            \Model\Base\ActiveRecord::undoCreates();
            self::$_logger->exception($ex);
            self::$_responseHandler->restartGame()
                ->setMessage($ex->getMessage()); // FIXME hide this in prod env
        } catch (\OptimisticLockException $ex) {
            \Driver\DbFactory::instance()->rollbackAll();

            \Model\Base\ActiveRecord::undoCreates();
            self::$_logger->exception($ex);
            $this->_setExceptionResponsePayload($ex, self::ERRNO_EXCEPTION_OPTIMISTICLOCK);
        } catch (\InvalidArgumentException $ex) {
            self::$_logger->exception($ex);

            self::$_responseHandler->restartGame()
                ->setMessage($ex->getMessage());
        } catch (\Funplus\Thrift\serverGated_serverGatedException $ex) {
            self::$_logger->exception($ex);

            self::$_responseHandler->restartGame()
                ->setMessage($ex->getMessage());
        } catch (\Thrift\Exception\TException $ex) {
            $faeClientBroken = TRUE;

            // unexpected exception
            self::$_logger->exception($ex);

            \Model\Base\ActiveRecord::undoCreates(); // FIXME can do that while fae broken?

            self::$_responseHandler->restartGame()
                ->setMessage($ex->getMessage());
        } catch (\Exception $ex) {
            \Driver\DbFactory::instance()->rollbackAll();

            self::$_logger->exception($ex);

            \Model\Base\ActiveRecord::undoCreates();

            $this->_setExceptionResponsePayload($ex, self::ERRNO_EXCEPTION_UNKNOWN);
        }

        \System\LockStep::releaseAll();

        $payloadSize = self::$_responseHandler->printResponse();

        if (\FaeEngine::isConnected() && !$faeClientBroken) {
            \FaeEngine::client()->gm_latency(
                \FaeEngine::ctx(),
                (int)((microtime(TRUE) - REQUEST_TIME_FLOAT) * 1000),
                $payloadSize
            );
        }

    }

}
