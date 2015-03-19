<?php

namespace Driver\MQ;

final class Beanstalk implements Driver, \Consts\ErrnoConst {

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
            throw new \ExpectedErrorException("Tube: $tube not defined in data/config/beanstalkd.php", self::ERRNO_SYS_INVALID_ARGUMENT);
        }

        $this->_conn = new \Pheanstalk_Pheanstalk($config['host'], $config['port'], self::TIMEOUT_DEFAULT);
        $this->_conn->useTube($tube);
        $this->_tube = $tube;
    }

    /**
     * @param array $message
     * @param array $channels
     * @param int $from
     * @param int $delay
     * @return int Job id
     */
    public function produce(array $message, array $channels, $from, $delay = 0) {
        $type = 'p'; // default pnb
        if (\Driver\IMFactory::ifUseRtm()) {
            $type = 'r';
        }
        return $this->_conn->putInTube(
            $this->_tube,
            $type . '|' . join(',', $channels) . '|' . $from . '|' . json_encode($this->_convertBigIntToString($message)),
            self::DEFAULT_URGENT_LEVEL,
            $delay,
            self::DEFAULT_TTR
        );
    }

    public function consume($timeout = NULL) {
        $job = $this->_conn->reserveFromTube($this->_tube, $timeout);
        if (!$job) {
            return array();
        }

        $data = json_decode($job->getData(), TRUE);
        return array(
            $job,
            $data['handler'],
            $data['params'],
        );
    }

    public function ackSuccess($job) {
        $this->_conn->delete($job);
    }

    public function ackFail($job) {
        // Buried jobs are put into a FIFO linked list and will not be touched by the server again until a client kicks them with the “kick” command
        // 'kick' will move the job to the ready queue if it is delayed or buried
        $this->_conn->bury($job);
    }

    public function revive($max) {
        return $this->_conn->kick($max);
    }

    // Pubnub C# client will convert bigint to scientific notation
    private function _convertBigIntToString(array $arr) {
        $maxInt32 = (1 << 32) - 1;
        array_walk_recursive($arr, function (&$value, $key) use ($maxInt32) {
            if (is_int($value) && $value > $maxInt32) {
                $value = (string)$value;
            }
        });
        return $arr;
    }

}
