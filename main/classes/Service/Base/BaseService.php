<?php

namespace Service\Base;

/**
 * Parent of all service[controller].
 *
 */
abstract class BaseService {

    const
        REPLY_PAYLOAD = 'payload',
        REPLY_SERVER_TIME = 'time';

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

    /**
     * @var \Manager\MetricsManager
     */
    protected $_metricsManager;

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

    // child can override this, but KEEP it protected
    protected function __construct() {
        $this->_metricsManager = \Manager\MetricsManager::getInstance();
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
     * Will overwrite previous payload.
     *
     * @param array $palyload
     */
    protected final function _setResponsePayload(array $palyload) {
        $this->response()->setPayload($palyload);
    }

    protected final static function _currentOpTime() {
        return \System\RequestHandler::getInstance()->currentOpTime();
    }

    public function beforeAction($params) {
        if (isset($params['uid'])) {
            \Model\JobModel::wakeupPendingJobs((int)$params['uid']);
        }
    }

    /**
     * A helper method to get current uid.
     *
     * Calculated from token.
     *
     * @return int|NULL
     */
    public final function getUid() {
        return self::$_request->getUid();
    }

}
