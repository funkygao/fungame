<?php
/**
 * 编写单元测试的教程.
 *
 * FAQ
 * ===
 * <p>
 * 1. 我写了多个测试用例，如何运行phpunit时只运行某一个测试用例？
 * shell$ phpunit --filter testHelloWorld UnitTestTutorial_Test.php
 *
 * 2. 运行phpunit时，怎么没有颜色？
 * shell$ phpunit --color
 *
 * 3. 执行phpunit时输出的.SEFI代表什么含义？
 * . 测试用例成功
 * E error
 * F fail
 * S skipped
 * I ignored
 * </p>
 *
 * 4. 如何重复指定次数的单元测试？
 * shell$ phpunit --repeat <times> UnitTestTutorial_Test.php
 *
 * Mock的限制
 * =========
 * <p>
 * 1. final、private、static无法mock
 * 2. 静态方法无法mock
 * </p>
 *
 * 编写单元测试的关键
 * ===============
 * 编写出可以测试的代码
 * <p>
 * 1. Avoid the hell that is global state
 *    This includes singletons、static methods, and global variables
 * 2. Use loosely coupled objects and dependency injection
 *    to wire them together
 * 3. Write short methods
 * </p>
 *
 */

// avoid 'Headers already sent' error
ob_start();

/**
 * 标准的单元测试例子.
 *
 * 不过，大家在使用时，最好继承{@link FunTestCaseBase}，而不是PHPUnit_Framework_TestCase，
 * 这样我们可以更方便扩充测试用例。
 *
 * 这里没有继承{@link FunTestCaseBase}，是为了让本测试用例可以在你的PC上跑起来，不依赖于
 * 我们的运行环境。
 *
 */
class UnitTestTutorial_Test extends PHPUnit_Framework_TestCase {

    /**
     * 先声明所用test case都可能使用的变量名称和类型，这样使用时就方便了.
     *
     * phpdoc里声明变量类型后，便于IDE进行自动补全.
     *
     * @var array
     */
    private $_sampleArray;


    /**
     * This is called before eeeeeeach test case(testXX method).
     *
     */
    public function setUp() {
        parent::setUp();  // never forget about this

        $this->_sampleArray = array(
            'foo' => 1,
            'bar' => 2
        );
    }

    /**
     * This is called after eeeeeeach test case(testXX method).
     *
     */
    public function tearDown() {
        // do my stuff

        parent::tearDown();
    }

    /**
     * @static
     *
     */
    public static function tearDownAfterClass() {
        parent::tearDownAfterClass();
    }

    /**
     * 介绍常用的assert方法.
     *
     * PHPUnit的断言
     * <p>
     * 布尔类型
     * assertTrue   断言为真
     * assertFalse  断言为假
     *
     * NULL类型
     * assertNull     断言为NULL
     * assertNotNull  断言非NULL
     *
     * 数字类型
     * assertEquals             断言等于
     * assertNotEquals          断言不等于
     * assertGreaterThan        断言大于
     * assertGreaterThanOrEqual 断言大于等于
     * assertLessThan           断言小于
     * assertLessThanOrEqual    断言小于等于
     *
     * 字符类型
     * assertEquals          断言等于
     * assertNotEquals       断言不等于
     * assertContains        断言包含
     * assertNotContains     断言不包含
     * assertContainsOnly    断言只包含
     * assertNotContainsOnly 断言不只包含
     *
     * 数组类型
     * assertEquals          断言等于
     * assertNotEquals       断言不等于
     * assertArrayHasKey     断言有键
     * assertArrayNotHasKey  断言没有键
     * assertContains        断言包含
     * assertNotContains     断言不包含
     * assertContainsOnly    断言只包含
     * assertNotContainsOnly 断言不只包含
     *
     * 对象类型
     * assertAttributeContains           断言属性包含
     * assertAttributeContainsOnly       断言属性只包含
     * assertAttributeEquals             断言属性等于
     * assertAttributeGreaterThan        断言属性大于
     * assertAttributeGreaterThanOrEqual 断言属性大于等于
     * assertAttributeLessThan           断言属性小于
     * assertAttributeLessThanOrEqual    断言属性小于等于
     * assertAttributeNotContains        断言不包含
     * assertAttributeNotContainsOnly    断言属性不只包含
     * assertAttributeNotEquals          断言属性不等于
     * assertAttributeNotSame            断言属性不相同
     * assertAttributeSame               断言属性相同
     * assertSame                        断言类型和值都相同
     * assertNotSame                     断言类型或值不相同
     * assertObjectHasAttribute          断言对象有某属性
     * assertObjectNotHasAttribute       断言对象没有某属性
     *
     * class类型
     * class类型包含对象类型的所有断言，还有
     * assertClassHasAttribute          断言类有某属性
     * assertClassHasStaticAttribute    断言类有某静态属性
     * assertClassNotHasAttribute       断言类没有某属性
     * assertClassNotHasStaticAttribute 断言类没有某静态属性
     *
     * 文件相关
     * assertFileEquals     断言文件内容等于
     * assertFileExists     断言文件存在
     * assertFileNotEquals  断言文件内容不等于
     * assertFileNotExists  断言文件不存在
     *
     * XML相关
     * assertXmlFileEqualsXmlFile        断言XML文件内容相等
     * assertXmlFileNotEqualsXmlFile     断言XML文件内容不相等
     * assertXmlStringEqualsXmlFile      断言XML字符串等于XML文件内容
     * assertXmlStringEqualsXmlString    断言XML字符串相等
     * assertXmlStringNotEqualsXmlFile   断言XML字符串不等于XML文件内容
     * assertXmlStringNotEqualsXmlString 断言XML字符串不相等
     * </p>
     *
     */
    public function testMostAsserts() {
        $this->assertArrayHasKey('foo', array('foo' => 'bar'));
        $this->assertClassHasAttribute('bar', 'SampleClassForTest');
        $this->assertContains(2, array(1, 2, 3));
        $this->assertContains('foo', 'spamfoobar');
        $this->assertStringStartsWith('foo', 'foobar');

        if (method_exists($this, 'assertCount'))
        {
            // PHP5.3+
            $this->assertCount(2, array(1, 'foo'));
        }

        $this->assertEmpty(array());
        $this->assertGreaterThan(1, 2);

        $this->assertInstanceOf('Exception', new RuntimeException());
        $this->assertInternalType('int', 5);
        $this->assertInternalType('integer', 5);

        $this->assertRegExp('/foo/', 'foo');

        // equals
        $expected = new stdClass();
        $expected->foo = 'foo';
        $expected->bar = 'bar';

        $actual = new stdClass();
        $actual->foo = 'foo';
        $actual->bar = 'bar';

        $this->assertEquals($expected, $actual);
        $this->assertEquals(array(1, 2, 3), array(1, 2, 1+2));
        $this->assertEquals(5, '5');  // 相当于 ==
        $this->assertNotSame(5, '5'); // 相当于 !==

        $this->assertFileExists(__FILE__);
        $this->assertFileEquals(__FILE__, 'UnitTestTutorial_Test.php');

        $this->assertEquals('UnitTestTutorial_Test::testMostAsserts', __METHOD__);
        $this->assertEquals('testMostAsserts', __FUNCTION__);
        $this->assertEquals('UnitTestTutorial_Test', __CLASS__);
        $this->assertGreaterThan(10, __LINE__);
    }

    /**
     * Demonstrate how to use dataProvider.
     *
     * @dataProvider arrayProvider A data provider method must be public and
     * either return an array of arrays or an object that implements the Iterator
     * interface and yields an array for each iteration step.
     *
     * 相当于：
     * <code>
     * foreach ($this->arrayProvider() as $value) {
     *     call_user_func_array(array($this, 'testAdd'), $value);
     * }
     * </code>
     *
     * @param $a
     * @param $b
     * @param $c
     */
    public function testAdd($a, $b, $c) {
        $this->assertEquals($c, $a + $b);
    }

    public function arrayProvider() {
        return array(
            array(0, 0, 0),
            array(0, 1, 1),
            array(1, 0, 1),
            array(3, 2, 5)
        );
    }

    /**
     * 有的测试用例，希望抛出某种异常，可以通过phpdoc expectedException来声明即可.
     *
     * 这样，如果测试用例没有抛出该异常，就会认为测试失败.
     *
     * 测试的异常要具体：PHPUnit不允许测试Exception这个异常，必须是它的子类(更具体)
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage invalid test
     * @expectedExceptionCode 11
     *
     */
    public function testException() {
        throw new InvalidArgumentException('invalid test', 11);
    }

    /**
     * Expecting an exception to be raised by the tested code.
     *
     */
    public function testSetExpectedException() {
        $this->setExpectedException('InvalidArgumentException', 'Right message', 32);
        throw new InvalidArgumentException('Right message', 32);
    }

    /**
     * 测试系统错误、警告等.
     *
     * PHPUnit converts PHP errors, warnings, and notices that are triggered
     * during the execution of a test to an exception.
     *
     * PHPUnit_Framework_Error_Notice and PHPUnit_Framework_Error_Warning represent
     * PHP notices and warnings, respectively.
     *
     * @expectedException PHPUnit_Framework_Error
     */
    public function testPhpErrors() {
        include 'non_exist_file.php';
    }

    public function testNonPublicAttributes() {
        $this->assertEquals(
            'bmw',
            $this->readAttribute(new SampleClassForTest(), 'bar')
        );
    }

    /**
     * 允许输出，而不仅仅assert的测试用例.
     *
     * 默认情况下，PHPUnit会把所有的输出(echo/print/var_dump等)给缓冲，不会输出
     *
     * 这里展示how to bypass this feature
     * 原理是让phpunit expect output一个值，而实际输出是另外一个值，这时它就会把assert异常打印出来
     *
     * 只能运行在 php5.3+
     */
    public function testOutput() {
        if (method_exists($this, 'expectOutputString')) {
            $this->expectOutputString('foo');
            echo "foo";
        } else {
            $this->markTestSkipped('needs php5.3+');
        }
    }

    /**
     * 有些用例还没有完成，但还要执行其他的测试用例，就像下面这样处理.
     *
     */
    public function testIncomplete() {
        $this->assertTrue(1 == 3 - 2);

        $this->markTestIncomplete("not implemented yet");
    }

    /**
     * 有些用例，想临时取消，就可以skip.
     *
     */
    public function testTestSkipped() {
        $this->markTestSkipped('skip me temporarily');
    }

    /**
     * 编写单元测试的简单例子.
     *
     * 不要用echo，那是给人看的；
     * 要用assertXXX方法，这样才能把测试自动化.
     */
    public function testHelloWorld() {
        $this->assertEquals(2, 1 + 1);
        $this->assertArrayHasKey('foo', $this->_sampleArray);
        $this->assertArrayNotHasKey("spam", $this->_sampleArray);
        $this->assertFalse(1 > 2);
        $this->assertTrue(1 < 2);
        $this->assertGreaterThan(2, 3);
        $this->assertLessThanOrEqual(2, 1);
        $this->assertEmpty('');
    }

    /**
     * Test getMock.
     *
     * 术语：
     * test double - 测试替身
     *
     * 原理:
     * <p>
     * getMock()会自动创建一个新的php class，它
     * - By default, all methods of the given class are replaced with a test double that
     *      just returns NULL unless a return value is configured using will($this->returnValue()), for instance
     * - When the second (optional) parameter is provided, only the methods whose names are
     *      in the array are replaced with a configurable test double. The behavior of the other methods is not changed
     * - The third (optional) parameter may hold a parameter array that is passed to the
     *      original class' constructor (which is not replaced with a dummy implementation by default)
     * - The fourth (optional) parameter can be used to specify a class name for the generated test double class
     * - The fifth (optional) parameter can be used to disable the call to the original class' constructor
     * - The sixth (optional) parameter can be used to disable the call to the original class' clone constructor
     * - The seventh (optional) parameter can be used to disable __autoload() during the generation of the test double class
     * </p>
     */
    public function testGetMock() {
        // Create a stub(替身) for the 'SomeClass' interface
        $stub = $this->getMock('SomeClass');

        // configure the stub
        $stub->expects($this->any())
            ->method('doSomething')
            ->will($this->returnValue('foo'));

        $this->assertEquals('foo', $stub->doSomething());
    }

    /**
     * Test getMockBuilder.
     *
     * getMockBuilder()与getMock()作用一样，只是可控性更强
     *
     * 可以控制:
     * <p>
     * - setMethods(array $methods) can be called on the Mock Builder object to specify the
     *      methods that are to be replaced with a configurable test double. The behavior
     *      of the other methods is not changed.
     * - setConstructorArgs(array $args) can be called to provide a parameter array that
     *      is passed to the original class' constructor (which is not replaced with a dummy implementation by default).
     * - setMockClassName($name) can be used to specify a class name for the generated test double class.
     * - disableOriginalConstructor() can be used to disable the call to the original class' constructor.
     * - disableOriginalClone() can be used to disable the call to the original class' clone constructor.
     * - disableAutoload() can be used to disable __autoload() during the generation of the test double class.
     * </p>
     *
     * @expectedException InvalidArgumentException
     */
    public function testGetMockBuilder() {
        // Create a stub(替身) for the 'SomeClassImpl' class
        $stub = $this->getMockBuilder('SomeClassImpl')
            ->disableOriginalConstructor()
            ->getMock();

        // configure the stub
        $stub->expects($this->any())
            ->method('doSomething')
            ->will($this->returnArgument(0));

        $this->assertEquals('foo', $stub->doSomething('foo'));
        $this->assertEquals('bar', $stub->doSomething('bar'));
        $this->assertEquals('spam', $stub->doSomething('spam'));

        // stub a method call to throw an exception
        $stub->expects($this->any())
            ->method('doSomething')
            ->will($this->throwException(new InvalidArgumentException()));
        $stub->doSomething();
    }

    /**
     * Another mock sample.
     *
     * with() - Each parameter is validated using PHPUnit constraints:
     * <ul>
     * <li>anything()</li>
     * <li>contains($value)</li>
     * <li>arrayHasKey($key)</li>
     * <li>equalTo($value, $delta, $maxDepth)</li>
     * <li>classHasAttribute($attribute)</li>
     * <li>greaterThan($value)</li>
     * <li>isInstanceOf($className)</li>
     * <li>isType($type)</li>
     * <li>matchesRegularExpression($regex)</li>
     * <li>stringContains($string, $case)</li>
     * </ul>
     *
     * withAnyParameters() is a quick way to say 'I dont care'
     *
     */
    public function testObserversAreUpdated() {
        // Create a mock for the Observer class,
        // only mock the update() method.
        $observer = $this->getMock('Observer', array('update'));

        // Set up the expectation for the update() method
        // to be called only once and with the string 'something'
        // as its parameter.
        //
        // The with() method can take any number of arguments, corresponding to
        // the number of parameters to the method being mocked.
        $observer->expects($this->once())
            ->method('update')
            ->with($this->equalTo('something'));

        $observer->expects($this->any())
            ->method('update')
            ->withAnyParameters(); // I dont care
        // You can specify more advanced constraints on the method argument than a simple match.
        /*
        $observer->expects($this->once())
            ->method('update')
            ->with(
                $this->greaterThan(0),
                $this->stringContains('something'),
                $this->anything());
         */

        $subject = new Subject();
        $subject->attach($observer);

        // Call the doSomething() method on the $subject object
        // which we expect to call the mocked Observer object's
        // update() method with the string 'something'.
        $subject->doSomething();

    }

    /**
     * Mock抽象类.
     *
     */
    public function testConcreteMethod() {
        $stub = $this->getMockForAbstractClass('AbstractClass');
        $stub->expects($this->any())
            ->method('abstractMethod')
            ->will($this->returnValue('blah'));

        $this->assertEquals('blah', $stub->concreteMethod());
    }

    /**
     * 依赖关系的例子.
     *
     * Producer部分
     *
     * A producer is a test method that yields its unit under test as return value.
     *
     * @return array
     */
    public function testDependencyProducer() {
        $stack = array();
        $this->assertEmpty($stack);

        return $stack;
    }

    /**
     * 依赖关系的例子.
     *
     * 既是Consumer，同时又是Producer
     *
     * A consumer is a test method that depends on one or more producers
     * and their return values.
     *
     * @param array $stack
     *
     * @return array
     *
     * @depends testDependencyProducer
     */
    public function testDependencyConsumer(array $stack) {
        array_push($stack, 'foo');
        $this->assertEquals('foo', $stack[count($stack) - 1]);
        $this->assertNotEmpty($stack);

        return $stack;
    }

    /**
     * 依赖关系的例子.
     *
     * Consumer部分
     *
     * @param array $stack
     *
     * @depends testDependencyConsumer
     */
    public function testDependencyConsumer2(array $stack) {
        $this->assertEquals('foo', array_pop($stack));
        $this->assertEmpty($stack);
    }

    /**
     * 起独立进程运行测试用例的例子.
     *
     * @runInSeparateProcess
     */
    public function testAnnotatedRunInSeparateProcess() {
        $this->assertEquals(1, 1);
    }

}

class SampleClassForTest {
    private $bar = 'bmw';

    public static function foo() {
        return 'foo';
    }

    public static function bar() {
        return self::foo();
    }

}

interface SomeClass {
    public function doSomething();
}

class SomeClassImpl implements SomeClass {
    public function doSomething() {
        return 'hello world';
    }
}

abstract class AbstractClass {
    public function concreteMethod() {
        return $this->abstractMethod();
    }

    public abstract function abstractMethod();
}

class Subject {
    protected $_observers = array();

    public function attach(Observer $observer) {
        $this->_observers[] = $observer;
    }

    public function doSomething() {
        $this->_notify('something');
    }

    private function _notify($msg) {
        foreach($this->_observers as $ob) {
            $ob->update($msg);
        }
    }
}

class Observer {
    public function update($msg) {
    }

    public function reportError($errorCode, $errorMsg, Subject $subject) {
    }

}

