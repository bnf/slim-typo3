<?php
namespace Bnf\SlimTypo3\Tests\Unit;

use Bnf\SlimTypo3\AppRegistry;
use Bnf\SlimTypo3\Http\SlimRequestHandler;
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
 * SlimRequestHandler
 *
 * @author Benjamin Franzke <bfr@qbus.de>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class SlimRequestHandlerTest extends UnitTestCase
{
    public function testGetPriority()
    {
        $bootstrap = $this->getMockBuilder(\TYPO3\CMS\Core\Core\Bootstrap::class)->disableOriginalConstructor()->getMock();
        $this->assertEquals(75, (new SlimRequestHandler($bootstrap))->getPriority());
    }

    public function testCanHandleRequestForEmptyApp()
    {
        $bootstrap = $this->getMockBuilder(\TYPO3\CMS\Core\Core\Bootstrap::class)->disableOriginalConstructor()->getMock();
        $handler = new SlimRequestHandler($bootstrap);

        $req = $this->mockRequest(['REQUEST_URI' => '/foo']);

        $this->assertFalse($handler->canHandleRequest($req));
    }

    public function testCanHandleRequest()
    {
        $bootstrap = $this->getMockBuilder(\TYPO3\CMS\Core\Core\Bootstrap::class)->disableOriginalConstructor()->getMock();
        $handler = new SlimRequestHandler($bootstrap);

        $req = $this->mockRequest(['REQUEST_URI' => '/foo']);

        $method = new \ReflectionMethod(SlimRequestHandler::class, 'getContainer');
        $method->setAccessible(true);
        $container = $method->invoke($handler, $req);
        $container->get('pimple')['settings'] = [
            'displayErrorDetails' => false,
            'outputBuffering' => false,
        ];
        $app = $container->get('app');

        $app->get('/foo', function ($req, $res) {
            return $res;
        });

        $this->assertTrue($handler->canHandleRequest($req));
    }

    public function testCanHandleRequestWithRouteArguments()
    {
        $bootstrap = $this->getMockBuilder(\TYPO3\CMS\Core\Core\Bootstrap::class)->disableOriginalConstructor()->getMock();
        $handler = new SlimRequestHandler($bootstrap);

        $req = $this->mockRequest(['REQUEST_URI' => '/foo/baz']);

        $method = new \ReflectionMethod(SlimRequestHandler::class, 'getContainer');
        $method->setAccessible(true);
        $container = $method->invoke($handler, $req);
        $container->get('pimple')['settings'] = [
            'displayErrorDetails' => false,
            'outputBuffering' => false,
        ];
        $app = $container->get('app');

        $app->get('/foo/{bar}', function ($req, $res) {
            return $res;
        });

        $this->assertTrue($handler->canHandleRequest($req));
    }

    /**
     * @test
     */
    public function testHandleRequest()
    {
        $bootstrap = $this->getMockBuilder(\TYPO3\CMS\Core\Core\Bootstrap::class)->disableOriginalConstructor()->getMock();
        $handler = new SlimRequestHandler($bootstrap);

        $req = $this->mockRequest(['REQUEST_URI' => '/foo']);

        /* Empty response that is will not be altered by \Slim\App::finalize */
        $headers = new Headers();
        $headers->set('Content-Length', '0');
        $response = new Response(200, $headers);

        $method = new \ReflectionMethod(SlimRequestHandler::class, 'getContainer');
        $method->setAccessible(true);
        $container = $method->invoke($handler, $req);
        $container->get('pimple')['settings'] = [
            'displayErrorDetails' => false,
            'outputBuffering' => false,
        ];
        $app = $container->get('app');

        $app->get('/foo', function ($req, $res) use ($response) {
            return $response;
        });
        $this->assertSame($response, $handler->handleRequest($req));
    }

    /**
     * @test
     */
    public function testCanThenHandleRequest()
    {
        $bootstrap = $this->getMockBuilder(\TYPO3\CMS\Core\Core\Bootstrap::class)->disableOriginalConstructor()->getMock();
        $handler = new SlimRequestHandler($bootstrap);

        $req = $this->mockRequest(['REQUEST_URI' => '/foo']);

        /* Empty response that is will not be altered by \Slim\App::finalize */
        $headers = new Headers();
        $headers->set('Content-Length', '0');
        $response = new Response(200, $headers);

        $method = new \ReflectionMethod(SlimRequestHandler::class, 'getContainer');
        $method->setAccessible(true);
        $container = $method->invoke($handler, $req);
        $container->get('pimple')['settings'] = [
            'displayErrorDetails' => false,
            'outputBuffering' => false,
        ];
        $app = $container->get('app');

        $app->get('/foo', function ($req, $res) use ($response) {
            return $response;
        });
        $this->assertTrue($handler->canHandleRequest($req));
        $this->assertSame($response, $handler->handleRequest($req));
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
        $handler = new SlimRequestHandler($bootstrap);
        $req = $this->mockRequest(['REQUEST_URI' => '/foo']);

        $method = new \ReflectionMethod(SlimRequestHandler::class, 'getContainer');
        $method->setAccessible(true);

        $executed = 0;
        $closure = function ($app) use (&$executed) {
            ++$executed;
        };
        $registry = GeneralUtility::makeInstance(AppRegistry::class);
        $registry->push($closure);

        $container = $method->invoke($handler, $req);
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
        $handler = new SlimRequestHandler($bootstrap);
        $req = $this->mockRequest(['REQUEST_URI' => '/foo']);

        $method = new \ReflectionMethod(SlimRequestHandler::class, 'getContainer');
        $method->setAccessible(true);

        $container = $method->invoke($handler, $req);
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
