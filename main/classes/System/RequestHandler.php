<?php

namespace System;

// TODO do we need ack like TCP?
final class RequestHandler implements \Consts\LoggerConst {

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

    public static function getInstance() {
        static $instance = NULL;
        if (NULL === $instance) {
            $instance = new self();
        }

        return $instance;
    }

    private function __construct() {
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

    public final function __clone() {
        throw new \Exception('You can not clone a singleton.');
    }

    public function validate() {
        if ($this->_uri != 'call/commit') {
            // 非batch请求的验证
            return $this;
        }

        // batch请求的验证

        // #. token and seq validation
        $params = $this->getParams();
        $token = $params['token'];
        if (!$token) {
            throw new \InvalidArgumentException("Empty token");
        }
        $uid = \Model\TokenModel::getInstance()->token2uid($token);
        if (!$uid) {
            throw new \InvalidArgumentException("Invalid token: $token");
        }
        $lastToken = $token;
        //$lastToken = \Driver\CacheFactory::instance()->get("token:$uid");
        if ($lastToken != $token) {
            // cheat, or concurrent login session
            // TODO
        }

        $seq = $params['seq'];
        if (!$seq) {
            throw new \InvalidArgumentException("Empty sequence");
        }
        $lastAckedSeq = 1; // TODO

        // #. cmds validation
        $cmds = $params['cmds'];
        if (count($cmds) > 80) { // 客户端目前队列的max len就是80，超了，就是挂机
            $n = count($cmds);
            self::$_logger->warn(self::CATEGORY_CHEAT, array(
                'type' => 'cmds',
                'n' => $n,
                'cmds' => $cmds,
            ));

            throw new BatchTooManyCommands("$n: " . json_encode($cmds)); // FIXME duplicated log
        }

        // validate optime, keep client/server time diff within acceptable limit
        $optimes = array_map(function ($cmd) {
            $opTime = (int)$cmd['at'];
            if (!$opTime) {
                throw new \InvalidArgumentException("No optime");
            }
            return $opTime;
        }, $cmds);
        $minOptime = min($optimes);
        $maxOptime = max($optimes);
        $commitTime = $params['ct'];
        if (!$commitTime) {
            throw new \InvalidArgumentException("Empty commit time");
        }
        $this->_validateOptime($commitTime, $minOptime, $maxOptime);
        $sentTime = $params['st']; // to calc round trip time
        if (time() - $sentTime > 3) { // FIXME should be in ms
            // round trip time so long?
            self::$_logger->warn('rtt', array(
                'sentTime' => $sentTime,
                'req' => $this->request()
            ));
        }

        return $this;
    }

    private function _validateOptime($commitTime, $minOptime, $maxOptime) {
        // 首先验证batch发送时间和每个command的optime不能超过XX秒
        $sendDiff = abs($commitTime, $maxOptime);

        $latency = abs($commitTime, $this->getRequestTime());

        // TODO borrow from RS IQ
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

    public function getUid() {
        $params = $this->getParams();
        if (!isset($params['token'])) {
            return NULL;
        }

        return \Model\TokenModel::getInstance()->token2uid($params['token']);
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
        if (!$opTime) {
            self::$_logger->warn(self::CATEGORY_WARNING, array(
                'msg' => 'empty opTime',
            ));

            $opTime = $this->getRequestTime();
        }

        $this->_opTime = $opTime;
    }

    /**
     * Get current action opTime.
     *
     * @return int
     */
    public function currentOpTime() {
        return $this->_opTime;
    }

    public function getRequestTimeFloat() {
        return $_SERVER['REQUEST_TIME_FLOAT'];
    }

    public function getRequestTime() {
        return $_SERVER['REQUEST_TIME'];
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

}

class BatchTooManyCommands extends \System\GameException {}
