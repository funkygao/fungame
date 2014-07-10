<?php

namespace Model\JobWorker\Base;

/**
 * Just a strategy(algorithm).
 *
 * Its state(data) all comes from model.
 *
 */
abstract class BaseWorker {

    /**
     * @var \Model\JobModel
     */
    protected $_jobModel;

    /**
     * What should we do when job is time up.
     */
    public abstract function onTimeout();

    public function __construct(\Model\JobModel $job) {
        $this->_jobModel = $job;
    }

    protected final function _getLogger() {
        return \System\Logger::getLogger(get_class($this));
    }

    // readony
    public final function __get($name) {
        return $this->_jobModel->$name;
    }

    protected final function _terminate() {
        $this->_jobModel->delete();
    }
    
}
