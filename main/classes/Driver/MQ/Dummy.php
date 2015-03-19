<?php

namespace Driver\MQ;

final class Dummy
    implements Driver, \Consts\LoggerConst {

    /**
     * @var \System\Logger
     */
    private $_logger;

    /**
     * @var string
     */
    private $_tube;

    public function init($tube) {
        $this->_tube = $tube;
        $this->_logger = \System\Logger::getLogger(__CLASS__);
    }

    /**
     * @param string $handlerName
     * @param array $message
     * @param int $delay
     * @return bool|int
     */
    public function produce(array $message, array $channels, $from, $delay = 0) {
        $this->_logger->info(self::CATEGORY_DEGRADE, array(
            'type' => 'mq',
            'tube' => $this->_tube,
            'handler' => $handlerName,
            'delay' => $delay,
            'msg' => $message,
        ));
    }

    public function consume($timeout = NULL) {
        return array();
    }

    public function ackSuccess($job) { }

    public function ackFail($job) { }

    public function revive($max) {
        return 0;
    }

}
