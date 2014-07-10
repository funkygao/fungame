<?php
/**
 * Parent class of all FunPlus unit test cases.
 *
 * Unit test FIRST principle:
 * <ul>
 * <li>Fast</li>
 * <li>Independent</li>
 * <li>Repeatable</li>
 * <li>Self-validating</li>
 * <li>Timely</li>
 * </ul>
 *
 * Usage:
 * <pre>
 * <?php
 *
 * require_once realpath(__DIR__ . '/../') . "/FunTestCaseBase.php";
 *
 * class MyClassTest extends FunTestCaseBase
 * {
 *     public function testSomething()
 *     {
 *         $this->assertEquals(1, 3-2);
 *     }
 * }
 * </pre>
 *
 */

require_once realpath(__DIR__ . "/../..") . '/main/init.php';

ini_set('memory_limit','4G');

abstract class FunTestCaseBase extends PHPUnit_Framework_TestCase {
    /**
     * @var float
     */
    protected $_beginAt;

    /**
     * @var float
     */
    protected $_duration;

    /**
     * @var array Array of ReflectionClass
     */
    private $_reflections = array();

    private function _getMilliSeconds() {
        list($usec, $sec) = explode(' ', microtime());
        return ((float)$usec + (float)$sec);
    }

    protected function setUp() {
        parent::setUp();

        date_default_timezone_set('Asia/Shanghai'); // avoid php warning

        $this->startTimer();
        \System\Appender\Factory::register('file:/var/log/dw/');
        \System\RequestHandler::getInstance()->setupRequestOpTime();
    }

    protected function tearDown() {
        parent::tearDown();

        \System\Flusher::getInstance()->flushAll();
    }

    /**
     * @return float
     */
    protected final function getDuration() {
        if ($this->_beginAt === NULL) {
            // forgot to start timer
            return 0;
        }

        $this->_duration = $this->_getMilliSeconds() - $this->_beginAt;
        return $this->_duration;
    }

    protected final function resetTimer() {
        $this->_duration = 0;
        $this->startTimer();
    }

    protected final function startTimer() {
        $this->_beginAt = $this->_getMilliSeconds();
    }

    protected final function stopTimer() {
        $this->_duration = $this->_getMilliSeconds() - $this->_beginAt;
        $this->_beginAt = NULL;
    }

    /**
     * Able to invoke class protected/private methods.
     *
     * php 5.3.2+
     *
     * <code>
     * $this->invokeMethod($userInfoModel, '_deductGold', array(10));
     * </code>
     *
     * @param object $object
     * @param string $methodName
     * @param array $args Array of arguments to pass into method.
     *
     * @return mixed
     */
    protected function invokeMethod($object, $methodName, array $args) {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $args);
    }

    protected function invokeStaticMethod($className, $methodName, array $args) {

    }

    protected function assertPrivateAttributeEquals($attrName, $expected, $object) {
        return $this->assertAttributeEquals($expected, $attrName, $object);
    }

    protected function lastLineOfFile($filename) {
        $content = file($filename);
        return $content[count($content) - 1];
    }

    protected function lineCountOfFile($filename) {
        return count(file($filename));
    }

}
