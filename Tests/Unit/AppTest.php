<?php
namespace Bnf\SlimTypo3\Tests\Unit;

use Bnf\SlimTypo3\App;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use Slim\Http\Body;
use Slim\Http\Environment;
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Http\RequestBody;
use Slim\Http\Response;
use Slim\Http\Uri;

/**
 * AppTest
 *
 * @author Benjamin Franzke <bfr@qbus.de>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class AppTest extends UnitTestCase
{
    public function testRegisterStoresClosure()
    {
        $closure = function() {};
        \Bnf\SlimTypo3\App::register($closure);
        $this->assertSame($closure, array_pop(\Bnf\SlimTypo3\App::getRegistrations()));
    }

    public function testCanHandleRequest()
    {
        $req = $this->mockRequest(['REQUEST_URI' => '/foo']);

        $container['request'] = $req;
        $app = new App($container);
        $app->get('/foo', function($req, $res) {
            return $res;
        });

        $this->assertTrue($app->canHandleRequest($req));
    }

    public function testCanNotHandleRequest()
    {
        $req = $this->mockRequest(['REQUEST_URI' => '/']);

        $container['request'] = $req;
        $app = new App($container);
        $app->get('/foo', function($req, $res) {
            return $res;
        });

        $this->assertFalse($app->canHandleRequest($req));
    }

    public function testDispatchRouterAndPrepareRoute()
    {
        $req = $this->mockRequest(['REQUEST_URI' => '/']);
        $container['request'] = $req;

        $app = new App($container);
        $method = new \ReflectionMethod(App::class, 'dispatchRouterAndPrepareRoute');
        $method->setAccessible(true);

        $req = $method->invoke($app, $req, $app->getContainer()->get('router'));
        $this->assertNotNull($req->getAttribute('routeInfo'));
    }

    public function testDispatchRouterAndPrepareRouteCachedRequest()
    {
        $req = $this->mockRequest(['REQUEST_URI' => '/']);
        $container['request'] = $req;

        $app = new App($container);
        $method = new \ReflectionMethod(App::class, 'dispatchRouterAndPrepareRoute');
        $method->setAccessible(true);

        $req1 = $method->invoke($app, $req, $app->getContainer()->get('router'));
        $req2 = $method->invoke($app, $req, $app->getContainer()->get('router'));
        $this->assertSame($req1, $req2);
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
}
