<?php

namespace Driver\MQ;

final class Beanstalk implements Driver {

    private $_tube;

    /**
     * @var \Pheanstalk_Pheanstalk
     */
    private $_conn;

    const TIMEOUT_DEFAULT = 1;
    const DEFAULT_TTR = 60; // how long the worker can reserve the job
    const DEFAULT_URGENT_LEVEL = 1024;

    public function init($tube) {
        $config = \System\Config::get('beanstalkd', $tube);
        if (!$config) {
            throw new \InvalidArgumentException("Tube: $tube not defined in data/config/beanstalkd.php");
        }

        $this->_conn = new \Pheanstalk_Pheanstalk($config['host'], $config['port'], self::TIMEOUT_DEFAULT);
        $this->_conn->useTube($tube);
        $this->_tube = $tube;
    }

    /**
     * @param string $handlerName
     * @param array $message
     * @param int $delay
     * @return bool|int
     */
    public function produce($handlerName, array $message, $delay = 0) {
        return $this->_conn->putInTube(
            $this->_tube,
            json_encode(array(
                'handler' => $handlerName,
                'params' => $message,
            )),
            self::DEFAULT_URGENT_LEVEL,
            $delay,
            self::DEFAULT_TTR
        );
    }

    public function consume($timeout = NULL) {
        $job = $this->_conn->reserveFromTube($this->_tube, $timeout);
        $data = json_decode($job->getData(), TRUE);
        return array(
            $job,
            $data['handler'],
            $data['params'],
        );
    }

    public function ackSuccess($job) {
        $this->_conn->bury($job);
    }

    public function ackFail($job) {
        $this->_conn->bury($job);
    }

}
