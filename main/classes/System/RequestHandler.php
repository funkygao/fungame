<?php

namespace System;

final class RequestHandler implements \Consts\LoggerConst, \Consts\AppConst,
    \Consts\ErrnoConst {

    // HTTP related attributes
    private $_request, $_cookie, $_get, $_post,  $_file, $_uri;

    /**
     * @var \System\Logger
     */
    private static $_logger;

    /**
     * The current request or action operation time.
     *
     * @var int
     */
    private $_opTime = 0;

    /**
     * Current player uid.
     *
     * May be empty. e,g. call.init
     *
     * @var int
     */
    private $_uid = 0;

    /*
     * Call commit
     */
    private $_isCommit = FALSE;

    private $_autoCallback = FALSE;

    public $transactional = FALSE;

    private static $_noTokenServices = array(
        'actor' => TRUE,
        'debug' => TRUE,
        'tools' => TRUE,
        'payment' => TRUE,
    );

    public static function getInstance() {
        static $instance = NULL;
        if (NULL === $instance) {
            $instance = new self();
        }

        return $instance;
    }

    public function __construct() {
        $input = json_decode(file_get_contents("php://input"), true); // TODO msgpack
        if ($input && is_array($input)) {
            $this->_request = array_merge($_REQUEST, $input);
        } else {
            $this->_request = $_REQUEST;
        }

        $this->_cookie = $_COOKIE;
        $this->_get = $_GET;
        $this->_post = $_POST;
        $this->_file = $_FILES;
        $uri = empty($_SERVER['REQUEST_URI']) ? '' : $_SERVER['REQUEST_URI'];
        $this->_uri = parse_url($uri, PHP_URL_PATH);
        $this->_uri = trim($this->_uri, '/');

        self::$_logger = \System\Logger::getLogger(__CLASS__);
    }

    public function __clone() {
        throw new \Exception('You can not clone a singleton.');
    }

    /**
     * @param string $serviceClassName
     * @return bool
     */
    public function isTokenlessService($serviceClassName) {
        return isset(self::$_noTokenServices[strtolower($serviceClassName)]);
    }

    public function validate() {
        $params = $this->getParams();

        if ($this->needToken()) {
            $payload = \Manager\UserManager::getInstance()->tokenPayload($params['token']);
            $this->setUid($payload['uid']);
        }

        if ($this->request('class') == 'actor'
            || ($this->request('class') == 'call'
                && $this->request('method') == 'commit')) {
            $this->_autoCallback = TRUE;
        }

        // FIXME: remove before release
        if ($params['ua'] == 'ci-robot') {
            $this->_autoCallback = TRUE;
            $this->setUid($params['uid']);
            return $this;
        }

        if ($this->request('class') != 'call'
            || $this->request('method') != 'commit') {
            // 非修改请求不验证
            return $this;
        }

        $this->_isCommit = TRUE;

        // #. gamedata版本号是否匹配
        $gamedataVer = $params['cv']; // config ver, sent from client
        $expectedVer = \System\GameData::getAssetsVersion();
        if ($gamedataVer != $expectedVer) {
            throw new \RestartGameException("Version mismatch: expected $expectedVer, got $gamedataVer");
        }

        $seq = $this->request('seq');
        if (!$seq) {
            throw new \ExpectedErrorException("Empty sequence", self::ERRNO_SYS_INVALID_ARGUMENT);
        }

        $redis = \Driver\RedisFactory::instance();
        $expectedSeq = $redis->get($this->getSeqRedisKey());
        if ($seq == $expectedSeq - 1) { // 回放上一次请求的result
            $lastResult = $redis->get($this->getLastResultRedisKey());
            \System\ResponseHandler::getInstance()->printHeader();
            print $lastResult;
            exit();
        } elseif ($seq != $expectedSeq) {
            self::$_logger->error('seqerr', array(
                'msg' => "Expected seq: $expectedSeq, got: $seq",
                'request' => $this->_request,
                'last_result' => $redis->get($this->getLastResultRedisKey()),
            ));
            throw new \RestartGameException("Seq key [{$this->getSeqRedisKey()}] expected: $expectedSeq, got: $seq");
        }

        return $this;
    }

    public function getParams() {
        static $params = NULL;
        if (NULL === $params) {
            $params = $this->request('params');
            if (!is_array($params)) {
                $params = json_decode($params, TRUE);
            }
        }

        return $params;
    }

    public function setupRequestOpTime() {
        /*
         * we can't trust user's params here
         * use server time although there will be lag
         */
        $this->_opTime = $this->getRequestTime();
        return $this;
    }

    public function setOpTime($opTime) {
        if (!$opTime || $opTime < 1407729834) {
            // 1407729834 = 2014-08-11
            self::$_logger->panic(self::CATEGORY_WARNING, array(
                'msg' => 'empty opTime',
            ));
        }

        $this->_opTime = $opTime;
    }

    /**
     * Get current action opTime.
     *
     * @return int
     */
    public function currentOpTime() {
        if ($this->_opTime) {
            return $this->_opTime;
        }
        return $this->getRequestTime();
    }

    public function getRequestTime() {
        return REQUEST_TIME;
    }

    public function setUid($uid) {
        $this->_uid = (int)$uid;
    }

    public function getUid() {
        return $this->_uid;
    }

    public function uri() {
        return $this->_uri;
    }

    public function request($key = NULL) {
        if ($key === null) {
            return $this->_request;
        }

        return array_deep_get($this->_request, $key, NULL);
    }

    public function uploaded($key = NULL) {
        if ($key === NULL) {
            return $this->_file;
        }

        return isset($this->_file[$key]) ? $this->_file[$key] : NULL;
    }

    public function enableTransaction() {
        $this->transactional = TRUE;
    }

    /**
     * @return array
     */
    public function export() {
        return $this->_request;
    }

    public function get($key = NULL) {
        if ($key === NULL) {
            return $this->_get;
        }

        return isset($this->_get[$key]) ? $this->_get[$key] : NULL;
    }

    public function cookie($key = NULL) {
        if ($key === NULL) {
            return $this->_cookie;
        }

        return isset($this->_cookie[$key]) ? $this->_cookie[$key] : NULL;
    }

    public function post($key = NULL) {
        if ($key === NULL) {
            return $this->_post;
        }

        return isset($this->_post[$key]) ? $this->_post[$key] : NULL;
    }

    public function actionName() {
        return $this->request('class') . ':' . $this->request('method');
    }

    public function faeReason() {
        $cmd = $this->request('params.op');
        if ($cmd) {
            $opInfo = array(
                'op' => $cmd,
                'args' => $this->request('params.args'),
            );
            $reason = json_encode($opInfo);
        } else {
            // non-batch call, e,g. actor callback
            $reason = $this->actionName();
        }

        return $reason;
    }

    public function isCommit() {
        return $this->_isCommit;
    }

    public function isAutoCallback() {
        return $this->_autoCallback;
    }

    // FIXME why?
    public function isFromActord() {
        return \System\RequestHandler::getInstance()->request('class') == self::CONTROLLER_CALLED_BY_SCHEDULER;
    }

    public function getSeqRedisKey() {
        return 'seq:' . $this->_uid;
    }

    public function getLastResultRedisKey() {
        return 'last_result:' . $this->_uid;
    }

    public function needToken() {
        $class = $this->request('class');
        $method = $this->request('method');
        $params = $this->getParams(); // FIXME: remove this when release

        if (self::isTokenlessService($class)
            || (isset($params['ua']) && $params['ua'] == 'ci-robot')) {
            return false;
        }

        if ($class != 'call' || $method == 'commit') {
            return true;
        }

        return false;
    }

}
