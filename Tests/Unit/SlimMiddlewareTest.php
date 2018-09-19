<?php
namespace Bnf\SlimTypo3\Tests\Unit;

use Bnf\SlimTypo3\AppRegistry;
use Bnf\SlimTypo3\Http\SlimMiddleware;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\App;
use Slim\Http\Environment;
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Http\RequestBody;
use Slim\Http\Response;
use Slim\Http\Uri;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * SlimMiddleware
 *
 * @author Benjamin Franzke <bfr@qbus.de>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class SlimMiddlewareTest extends UnitTestCase
{
    /**
     * @var bool
     */
    protected $resetSingletonInstances = true;

    /**
     * RequestHandlerInterface
     */
    protected $requestHandler;

    protected function setUp()
    {
        $this->responseProphecy = $this->prophesize();
        $this->responseProphecy->willImplement(ResponseInterface::class);

        $this->requestHandler = $this->prophesize();
        $this->requestHandler->willImplement(RequestHandlerInterface::class);
        $this->requestHandler->handle(Argument::any())->willReturn($this->responseProphecy->reveal());
    }

    public function testCanHandleRequestForEmptyApp()
    {
        $bootstrap = $this->getMockBuilder(\TYPO3\CMS\Core\Core\Bootstrap::class)->disableOriginalConstructor()->getMock();
        $middleware = new SlimMiddleware($bootstrap);

        $req = $this->mockRequest(['REQUEST_URI' => '/foo']);
        $this->assertSame($middleware->process($req, $this->requestHandler->reveal()), $this->responseProphecy->reveal());
    }

    public function testCanHandleRequest()
    {
        $bootstrap = $this->getMockBuilder(\TYPO3\CMS\Core\Core\Bootstrap::class)->disableOriginalConstructor()->getMock();
        $middleware = new SlimMiddleware($bootstrap);

        $req = $this->mockRequest(['REQUEST_URI' => '/foo']);

        $method = new \ReflectionMethod(SlimMiddleware::class, 'getContainer');
        $method->setAccessible(true);
        $container = $method->invoke($middleware, $req);
        $container->get('pimple')['settings'] = [
            'displayErrorDetails' => false,
            'outputBuffering' => false,
        ];
        $app = $container->get('app');

        $app->get('/foo', function ($req, $res) {
            return $res->withStatus(201);
        });

        $this->assertEquals(201, $middleware->process($req, $this->requestHandler->reveal())->getStatusCode());
    }

    public function testCanHandleRequestWithRouteArguments()
    {
        $bootstrap = $this->getMockBuilder(\TYPO3\CMS\Core\Core\Bootstrap::class)->disableOriginalConstructor()->getMock();
        $middleware = new SlimMiddleware($bootstrap);

        $req = $this->mockRequest(['REQUEST_URI' => '/foo/baz']);

        $method = new \ReflectionMethod(SlimMiddleware::class, 'getContainer');
        $method->setAccessible(true);
        $container = $method->invoke($middleware, $req);
        $container->get('pimple')['settings'] = [
            'displayErrorDetails' => false,
            'outputBuffering' => false,
        ];
        $app = $container->get('app');

        $app->get('/foo/{bar}', function ($req, $res) {
            return $res->withStatus(201);
        });

        $this->assertEquals(201, $middleware->process($req, $this->requestHandler->reveal())->getStatusCode());
    }

    /**
     * @test
     */
    public function testHandleRequest()
    {
        $bootstrap = $this->getMockBuilder(\TYPO3\CMS\Core\Core\Bootstrap::class)->disableOriginalConstructor()->getMock();
        $middleware = new SlimMiddleware($bootstrap);

        $req = $this->mockRequest(['REQUEST_URI' => '/foo']);

        /* Empty response that is will not be altered by \Slim\App::finalize */
        $headers = new Headers();
        $headers->set('Content-Length', '0');
        $response = new Response(200, $headers);

        $method = new \ReflectionMethod(SlimMiddleware::class, 'getContainer');
        $method->setAccessible(true);
        $container = $method->invoke($middleware, $req);
        $container->get('pimple')['settings'] = [
            'displayErrorDetails' => false,
            'outputBuffering' => false,
        ];
        $app = $container->get('app');

        $app->get('/foo', function ($req, $res) use ($response) {
            return $response->withStatus(201);
        });
        $this->assertEquals(201, $middleware->process($req, $this->requestHandler->reveal())->getStatusCode());
    }

    protected function mockRequest($env = [])
    {
        $env += [
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/',
            'REQUEST_METHOD' => 'GET',
        ];

        $env = Environment::mock($env);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new RequestBody();
        $req = new Request($env['REQUEST_METHOD'], $uri, $headers, $cookies, $serverParams, $body);

        return $req;
    }

    public function testGetAppWithRegistry()
    {
        $bootstrap = $this->getMockBuilder(\TYPO3\CMS\Core\Core\Bootstrap::class)->disableOriginalConstructor()->getMock();
        $middleware = new SlimMiddleware($bootstrap);
        $req = $this->mockRequest(['REQUEST_URI' => '/foo']);

        $method = new \ReflectionMethod(SlimMiddleware::class, 'getContainer');
        $method->setAccessible(true);

        $executed = 0;
        $closure = function ($app) use (&$executed) {
            ++$executed;
        };
        $registry = GeneralUtility::makeInstance(AppRegistry::class);
        $registry->push($closure);

        $container = $method->invoke($middleware, $req);
        $container->get('pimple')['settings'] = [
            'displayErrorDetails' => false,
            'outputBuffering' => false,
        ];
        $app = $container->get('app');

        $this->assertSame(get_class($app), App::class);
        $this->assertSame($closure, $registry->pop());
        $this->assertEquals(1, $executed);
    }

    public function testContainer()
    {
        $bootstrap = $this->getMockBuilder(\TYPO3\CMS\Core\Core\Bootstrap::class)->disableOriginalConstructor()->getMock();
        $middleware = new SlimMiddleware($bootstrap);
        $req = $this->mockRequest(['REQUEST_URI' => '/foo']);

        $method = new \ReflectionMethod(SlimMiddleware::class, 'getContainer');
        $method->setAccessible(true);

        $container = $method->invoke($middleware, $req);
        $container->get('pimple')['settings'] = [
            'displayErrorDetails' => false,
            'outputBuffering' => false,
        ];

        $this->assertSame(\Slim\Handlers\Error::class, get_class($container->get('errorHandler')));
        $this->assertSame(\Slim\Handlers\PhpError::class, get_class($container->get('phpErrorHandler')));
        $this->assertSame(\Slim\Handlers\NotFound::class, get_class($container->get('notFoundHandler')));
        $this->assertSame(\Slim\Handlers\NotAllowed::class, get_class($container->get('notAllowedHandler')));
    }
}
