<?php
namespace Bnf\SlimTypo3\Tests\Unit;

use Bnf\SlimTypo3\CallableResolver;
use Bnf\SlimTypo3\Tests\Unit\Mocks\CallableTest;
use Bnf\SlimTypo3\Tests\Unit\Mocks\InvokableTest;
use Slim\Container;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * CallableResolverTest
 *
 * @author Benjamin Franzke <bfr@qbus.de>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class CallableResolverTest extends UnitTestCase
{
    /**
     * @var Container
     */
    protected $container;

    public function setUp(): void
    {
        CallableTest::$CalledCount = 0;
        InvokableTest::$CalledCount = 0;
        $this->container = new Container();
    }

    public function testClosure()
    {
        $test = function () {
            static $called_count = 0;

            return $called_count++;
        };
        $resolver = new CallableResolver($this->container);
        $callable = $resolver->resolve($test);
        $callable();
        $this->assertEquals(1, $callable());
    }

    public function testFunctionName()
    {
        // @codingStandardsIgnoreStart
        function testCallable()
        {
            static $called_count = 0;

            return $called_count++;
        };
        // @codingStandardsIgnoreEnd

        $resolver = new CallableResolver($this->container);
        $callable = $resolver->resolve(__NAMESPACE__ . '\testCallable');
        $callable();
        $this->assertEquals(1, $callable());
    }

    public function testObjMethodArray()
    {
        $obj = new CallableTest();
        $resolver = new CallableResolver($this->container);
        $callable = $resolver->resolve([$obj, 'toCall']);
        $callable();
        $this->assertEquals(1, CallableTest::$CalledCount);
    }

    public function testSlimCallable()
    {
        $resolver = new CallableResolver($this->container);
        $callable = $resolver->resolve(CallableTest::class . ':toCall');
        $callable();
        $this->assertEquals(1, CallableTest::$CalledCount);
    }

    public function testSlimCallableTypo3Notation()
    {
        $resolver = new CallableResolver($this->container);
        $callable = $resolver->resolve(CallableTest::class . '->toCall');
        $callable();
        $this->assertEquals(1, CallableTest::$CalledCount);
    }

    public function testSlimCallableTypo3NotationLeadingSlash()
    {
        $resolver = new CallableResolver($this->container);
        $this->expectException('\InvalidArgumentException');
        $this->expectExceptionMessage('must not start with a backslash');
        $callable = $resolver->resolve('\\' . CallableTest::class . '->toCall');
    }

    public function testSlimCallableContainer()
    {
        $resolver = new CallableResolver($this->container);
        $resolver->resolve(CallableTest::class . ':toCall');
        $this->assertEquals($this->container, CallableTest::$CalledContainer);
    }

    public function testContainer()
    {
        $this->container['callable_service'] = new CallableTest();
        $resolver = new CallableResolver($this->container);
        $callable = $resolver->resolve('callable_service:toCall');
        $callable();
        $this->assertEquals(1, CallableTest::$CalledCount);
    }

    public function testResolutionToAnInvokableClassInContainer()
    {
        $this->container['an_invokable'] = function ($c) {
            return new InvokableTest();
        };
        $resolver = new CallableResolver($this->container);
        $callable = $resolver->resolve('an_invokable');
        $callable();
        $this->assertEquals(1, InvokableTest::$CalledCount);
    }

    public function testResolutionToAnInvokableClass()
    {
        $resolver = new CallableResolver($this->container);
        $callable = $resolver->resolve(InvokableTest::class);
        $callable();
        $this->assertEquals(1, InvokableTest::$CalledCount);
    }

    public function testMethodNotFoundThrowException()
    {
        $this->container['callable_service'] = new CallableTest();
        $resolver = new CallableResolver($this->container);
        $this->expectException('\RuntimeException');
        $resolver->resolve('callable_service:noFound');
    }

    public function testFunctionNotFoundThrowException()
    {
        $resolver = new CallableResolver($this->container);
        $this->expectException('\RuntimeException');
        $resolver->resolve('noFound');
    }

    public function testClassNotFoundThrowException()
    {
        $resolver = new CallableResolver($this->container);
        $this->expectException('\RuntimeException');
        $this->expectExceptionMessage('Callable Unknown does not exist');
        $resolver->resolve('Unknown:notFound');
    }

    public function testCallableClassNotFoundThrowException()
    {
        $resolver = new CallableResolver($this->container);
        $this->expectException('\RuntimeException');
        $this->expectExceptionMessage('is not resolvable');
        $resolver->resolve(['Unknown', 'notFound']);
    }

    public function testCallableInvalidTypeThrowException()
    {
        $resolver = new CallableResolver($this->container);
        $this->expectException('\RuntimeException');
        $this->expectExceptionMessage('is not resolvable');
        $resolver->resolve(__LINE__);
    }
}
