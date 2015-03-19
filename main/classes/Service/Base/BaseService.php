<?php

namespace Services\Base;

/**
 * Parent of all service[controller].
 *
 */
abstract class BaseService
    implements \Consts\ErrnoConst {

    /**
     * Request input verifier[validator].
     *
     * @var \Utils\VerifyInputs
     */
    private static $_verifier = NULL;

    /**
     * @var \System\ResponseHandler
     */
    private static $_response;

    /**
     * @var \System\RequestHandler
     */
    private static $_request;

    protected function __construct() {
    }

    /**
     * @param \System\RequestHandler $request
     * @param \System\ResponseHandler $response
     * @return BaseService
     */
    public static final function getInstance(\System\RequestHandler $request,
                                           \System\ResponseHandler $response) {
        static $instances = array();
        $clsName = get_called_class();
        if (!isset($instances[$clsName])) {
            $instances[$clsName] = new $clsName();
        }

        // all controllers share the single response|request handler
        if (NULL != $response) {
            // called only once per http request
            self::$_response = $response;
            self::$_request = $request;
        }

        return $instances[$clsName];
    }

    public final function __clone() {
        throw new \Exception('You can not clone a singleton.');
    }

    protected final function _verifyInt( /* $arg1, $arg2, ... */) {
        if (self::$_verifier === NULL) {
            self::$_verifier = \Utils\VerifyInputs::getInstance();
        }

        call_user_func_array(array(self::$_verifier, 'int'), func_get_args());
    }

    /**
     * A handy func so that children needn't depend on \System\ResponseHandler.
     *
     * @return \System\ResponseHandler
     */
    protected final function response() {
        return self::$_response;
    }

    /**
     * A handy func so that children needn't depend on \System\ResponseHandler.
     *
     * @return \System\RequestHandler
     */
    protected final function request() {
        return self::$_request;
    }

    protected final function _getLogger() {
        return \System\Logger::getLogger(get_class($this));
    }

    /**
     * @deprecated
     */
    protected final function _enableTransaction() {
        self::$_request->enableTransaction();
    }

    protected final function _lockUser($uid, $reason = '') {
        \System\LockStep::lockUser($uid, $reason);
    }

    /**
     * Will overwrite previous payload.
     *
     * @param array $palyload
     */
    protected final function _setResponsePayload(array $palyload) {
        $this->response()->setPayload($palyload);
    }

    protected final static function _currentOpTime() {
        return self::$_request->currentOpTime();
    }

}
