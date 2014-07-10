<?php

namespace Model;

/**
 * Delayed job(timer) sheduler.
 */
final class JobModel extends Gen\JobRecord
    implements \Consts\JobConst {

    /**
     * Strategy pattern.
     *
     * @var \Model\JobWorker\Base\BaseWorker
     */
    public $jobWorker;

    /**
     * Making job execute only once.
     *
     * @var bool
     */
    private $_performed = false;

    protected function _init(array $row) {
        $this->jobWorker = \Model\JobWorker\Factory::build($this);
    }

    public function terminate() {
        $this->delete();
    }

    public function isTimeout() {
        return self::currentOpTime() >= $this->time_end;
    }

    public function wakeup() {
        if ($this->isTimeout() && !$this->_performed) {
            try {
                $this->jobWorker->onTimeout();
            } catch (\Exception $ex) {
                // if job throws exception, it will block player forever
                $this->_getLogger()->warn('job', array(
                    'job' => $this->export(array()),
                ));

                $this->terminate();
            }

            $this->_performed = true;
        }
    }

    public function slideWindow($delta) {
        $this->time_start += $delta;
        $this->time_end += $delta;
        return $this;
    }

    public function sleepAgain($duration) {
        $this->time_start = $this->time_end;
        $this->time_end = $this->time_start + $duration;
    }

    public function sleepMore($delta) {
        if ($delta < 0) {
            throw new \InvalidArgumentException("$delta < 0");
        }

        $this->time_end += $delta;
        return $this;
    }

    public function sleepLess($delta) {
        if ($delta < 0) {
            throw new \InvalidArgumentException("$delta < 0");
        }

        $this->time_end -= $delta;
        return $this;
    }

    public static function wakeupPendingJobs($uid) {
        $jobs = self::getAll($uid);
        // sort by time_end asc
        usort($jobs, function ($job1, $job2) {
            if ($job1->time_end > $job2->time_end) {
                return 1;
            } elseif ($job1->time_end == $job2->time_end) {
                return 0;
            } else {
                return -1;
            }
        });

        foreach ($jobs as $jobModel) {
            $jobModel->wakeup();
        }
    }

    public static function submitJob($uid, $cityId, $eventType,
                                     $startTime, $duration, array $trace) {
        if(!$eventType || !$startTime || !$trace) {
            throw new \InvalidArgumentException("Job properties miss: " .
                var_export(array($uid, $eventType, $startTime, $duration, $trace), TRUE));
        }

        $jobId = self::nextHintId();
        $row = array(
            'uid' => $uid,
            'city_id' => $cityId,
            'job_id' => $jobId,
            'event_type' => $eventType,
            'time_start' => $startTime,
            'time_end' => $startTime + $duration,
            'trace' => $trace,
        );
        if (NULL == self::create($row)) {
            throw new \System\ServerException();
        }

        return $jobId;
    }

    public static function findAllByType($uid, $jobType) {
        $ret = array();
        foreach (self::getAll($uid) as $job) {
            if ($job->event_type == $jobType) {
                $ret[] = $job;
            }
        }
        return $ret;
    }

    public static function getByType($uid, $jobType) {
        $jobs = self::findAllByType($uid, $jobType);
        if (count($jobs) > 1) {
            throw new \System\GameException("Job type: $jobType should be single for uid: $uid");
        }

        return $jobs[0];
    }

    public static function exportJobs($uid) {
        $ret = array();
        foreach (self::getAll($uid) as $job) {
            $exportedJob = $job->export(
                array('uid', 'ctime', 'mtime')
            );

            $ret[$job->job_id] = $exportedJob;
        }

        return $ret;
    }

}
