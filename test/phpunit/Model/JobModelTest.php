<?php

require_once realpath(__DIR__ . '/../') . "/FunTestCaseBase.php";

class JobModelTest extends FunTestCaseBase implements \Consts\JobConst {

    private $_uid = 1;
    private $_cityId = 1;

    protected function tearDown() {
        parent::tearDown();

        \Model\JobModel::tearDownForUnitTest();
        \Model\JobModel::table()->query(2, 'DELETE FROM Job');
    }

    private function _prepareNewJob() {
        $duration = 10;
        $jobId = \Model\JobModel::submitJob($this->_uid, $this->_cityId,
            self::EVENT_TYPE_PVP_MARCH, time(), $duration, array('x'=>'y'));
        return $jobId;
    }

    public function testSubmitJob() {
        $jobId = $this->_prepareNewJob();
        $this->assertGreaterThanOrEqual(1, $jobId);

        $jobModel = \Model\JobModel::get($this->_uid, $jobId);
        $this->assertFalse($jobModel->isTimeout()); // within 10s
        $this->assertInstanceOf('\Model\JobWorker\PvpMarch', $jobModel->jobWorker);
    }

    public function testWakeupPendingJob() {
        $this->_prepareNewJob();
        \Model\JobModel::wakeupPendingJobs(1);
        $this->assertTrue(TRUE);
    }

    public function testChangeTimeWindow() {
        $jobId = $this->_prepareNewJob();
        $job = \Model\JobModel::get($this->_uid, $jobId);
        $t1 = $job->time_start;
        $t2 = $job->time_end;
        $this->assertEquals(10, $t2 - $t1);

        // slide window
        $job->slideWindow(5);
        $this->assertTrue($job->isDirty());
        $this->assertEquals($t1 + 5, $job->time_start);
        $this->assertEquals($t2 + 5, $job->time_end);

        $t1 = $job->time_start;
        $t2 = $job->time_end;
        $job->sleepMore(3);
        $this->assertEquals($t1, $job->time_start);
        $this->assertEquals($t2 + 3, $job->time_end);
    }

    public function testGetTypeLabel() {
        $jobId = $this->_prepareNewJob();
        $job = \Model\JobModel::get($this->_uid, $jobId);
        $this->assertEquals('pvp.march', $job->getTypeLabel());
    }

}
