<?php

namespace Driver\MQ;

// Naming borrowed from RabbitMQ instead of Beanstalkd because it more MQ abstraction
interface Driver {

    /**
     * @param string $tube
     * @return void
     */
    public function init($tube);

    /**
     * @param array $message
     * @param array $channels
     * @param int $from
     * @param int $delay
     * @return int Job id of new message
     */
    public function produce(array $message, array $channels, $from, $delay = 0);

    /**
     * @param int $timeout
     * @return array [job, handler, params]
     */
    public function consume($timeout = 0);

    /**
     * After consuming a job, tell the broker how to handle the job.
     *
     * @param object $job
     * @return void
     */
    public function ackSuccess($job);

    /**
     * After consuming a job, tell the broker how to handle the job.
     *
     * @param object $job
     * @return void
     */
    public function ackFail($job);

    /**
     * Move failed jobs to ready queue.
     *
     * @param int $max
     * @return int Number of revived jobs
     */
    public function revive($max);

}

