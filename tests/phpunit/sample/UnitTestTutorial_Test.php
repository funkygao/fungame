<?php
/**
 * ��д��Ԫ���ԵĽ̳�.
 *
 * FAQ
 * ===
 * <p>
 * 1. ��д�˶�������������������phpunitʱֻ����ĳһ������������
 * shell$ phpunit --filter testHelloWorld UnitTestTutorial_Test.php
 *
 * 2. ����phpunitʱ����ôû����ɫ��
 * shell$ phpunit --color
 *
 * 3. ִ��phpunitʱ�����.SEFI����ʲô���壿
 * . ���������ɹ�
 * E error
 * F fail
 * S skipped
 * I ignored
 * </p>
 *
 * 4. ����ظ�ָ�������ĵ�Ԫ���ԣ�
 * shell$ phpunit --repeat <times> UnitTestTutorial_Test.php
 *
 * Mock������
 * =========
 * <p>
 * 1. final��private��static�޷�mock
 * 2. ��̬�����޷�mock
 * </p>
 *
 * ��д��Ԫ���ԵĹؼ�
 * ===============
 * ��д�����Բ��ԵĴ���
 * <p>
 * 1. Avoid the hell that is global state
 *    This includes singletons��static methods, and global variables
 * 2. Use loosely coupled objects and dependency injection
 *    to wire them together
 * 3. Write short methods
 * </p>
 *
 */

// avoid 'Headers already sent' error
ob_start();

/**
 * ��׼�ĵ�Ԫ��������.
 *
 * �����������ʹ��ʱ����ü̳�{@link FunTestCaseBase}��������PHPUnit_Framework_TestCase��
 * �������ǿ��Ը������������������
 *
 * ����û�м̳�{@link FunTestCaseBase}����Ϊ���ñ������������������PC������������������
 * ���ǵ����л�����
 *
 */
class UnitTestTutorial_Test extends PHPUnit_Framework_TestCase {

    /**
     * ����������test case������ʹ�õı������ƺ����ͣ�����ʹ��ʱ�ͷ�����.
     *
     * phpdoc�������������ͺ󣬱���IDE�����Զ���ȫ.
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
     * ���ܳ��õ�assert����.
     *
     * PHPUnit�Ķ���
     * <p>
     * ��������
     * assertTrue   ����Ϊ��
     * assertFalse  ����Ϊ��
     *
     * NULL����
     * assertNull     ����ΪNULL
     * assertNotNull  ���Է�NULL
     *
     * ��������
     * assertEquals             ���Ե���
     * assertNotEquals          ���Բ�����
     * assertGreaterThan        ���Դ���
     * assertGreaterThanOrEqual ���Դ��ڵ���
     * assertLessThan           ����С��
     * assertLessThanOrEqual    ����С�ڵ���
     *
     * �ַ�����
     * assertEquals          ���Ե���
     * assertNotEquals       ���Բ�����
     * assertContains        ���԰���
     * assertNotContains     ���Բ�����
     * assertContainsOnly    ����ֻ����
     * assertNotContainsOnly ���Բ�ֻ����
     *
     * ��������
     * assertEquals          ���Ե���
     * assertNotEquals       ���Բ�����
     * assertArrayHasKey     �����м�
     * assertArrayNotHasKey  ����û�м�
     * assertContains        ���԰���
     * assertNotContains     ���Բ�����
     * assertContainsOnly    ����ֻ����
     * assertNotContainsOnly ���Բ�ֻ����
     *
     * ��������
     * assertAttributeContains           �������԰���
     * assertAttributeContainsOnly       ��������ֻ����
     * assertAttributeEquals             �������Ե���
     * assertAttributeGreaterThan        �������Դ���
     * assertAttributeGreaterThanOrEqual �������Դ��ڵ���
     * assertAttributeLessThan           ��������С��
     * assertAttributeLessThanOrEqual    ��������С�ڵ���
     * assertAttributeNotContains        ���Բ�����
     * assertAttributeNotContainsOnly    �������Բ�ֻ����
     * assertAttributeNotEquals          �������Բ�����
     * assertAttributeNotSame            �������Բ���ͬ
     * assertAttributeSame               ����������ͬ
     * assertSame                        �������ͺ�ֵ����ͬ
     * assertNotSame                     �������ͻ�ֵ����ͬ
     * assertObjectHasAttribute          ���Զ�����ĳ����
     * assertObjectNotHasAttribute       ���Զ���û��ĳ����
     *
     * class����
     * class���Ͱ����������͵����ж��ԣ�����
     * assertClassHasAttribute          ��������ĳ����
     * assertClassHasStaticAttribute    ��������ĳ��̬����
     * assertClassNotHasAttribute       ������û��ĳ����
     * assertClassNotHasStaticAttribute ������û��ĳ��̬����
     *
     * �ļ����
     * assertFileEquals     �����ļ����ݵ���
     * assertFileExists     �����ļ�����
     * assertFileNotEquals  �����ļ����ݲ�����
     * assertFileNotExists  �����ļ�������
     *
     * XML���
     * assertXmlFileEqualsXmlFile        ����XML�ļ��������
     * assertXmlFileNotEqualsXmlFile     ����XML�ļ����ݲ����
     * assertXmlStringEqualsXmlFile      ����XML�ַ�������XML�ļ�����
     * assertXmlStringEqualsXmlString    ����XML�ַ������
     * assertXmlStringNotEqualsXmlFile   ����XML�ַ���������XML�ļ�����
     * assertXmlStringNotEqualsXmlString ����XML�ַ��������
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
        $this->assertEquals(5, '5');  // �൱�� ==
        $this->assertNotSame(5, '5'); // �൱�� !==

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
     * �൱�ڣ�
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
     * �еĲ���������ϣ���׳�ĳ���쳣������ͨ��phpdoc expectedException����������.
     *
     * �����������������û���׳����쳣���ͻ���Ϊ����ʧ��.
     *
     * ���Ե��쳣Ҫ���壺PHPUnit���������Exception����쳣����������������(������)
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
     * ����ϵͳ���󡢾����.
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
     * �����������������assert�Ĳ�������.
     *
     * Ĭ������£�PHPUnit������е����(echo/print/var_dump��)�����壬�������
     *
     * ����չʾhow to bypass this feature
     * ԭ������phpunit expect outputһ��ֵ����ʵ�����������һ��ֵ����ʱ���ͻ��assert�쳣��ӡ����
     *
     * ֻ�������� php5.3+
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
     * ��Щ������û����ɣ�����Ҫִ�������Ĳ�������������������������.
     *
     */
    public function testIncomplete() {
        $this->assertTrue(1 == 3 - 2);

        $this->markTestIncomplete("not implemented yet");
    }

    /**
     * ��Щ����������ʱȡ�����Ϳ���skip.
     *
     */
    public function testTestSkipped() {
        $this->markTestSkipped('skip me temporarily');
    }

    /**
     * ��д��Ԫ���Եļ�����.
     *
     * ��Ҫ��echo�����Ǹ��˿��ģ�
     * Ҫ��assertXXX�������������ܰѲ����Զ���.
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
     * ���
     * test double - ��������
     *
     * ԭ��:
     * <p>
     * getMock()���Զ�����һ���µ�php class����
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
        // Create a stub(����) for the 'SomeClass' interface
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
     * getMockBuilder()��getMock()����һ����ֻ�ǿɿ��Ը�ǿ
     *
     * ���Կ���:
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
        // Create a stub(����) for the 'SomeClassImpl' class
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
     * Mock������.
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
     * ������ϵ������.
     *
     * Producer����
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
     * ������ϵ������.
     *
     * ����Consumer��ͬʱ����Producer
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
     * ������ϵ������.
     *
     * Consumer����
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
     * ������������в�������������.
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

