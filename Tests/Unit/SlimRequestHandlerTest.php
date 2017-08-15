<?php
namespace Bnf\SlimTypo3\Tests\Unit;

use Bnf\SlimTypo3\App;
use Bnf\SlimTypo3\Http\SlimRequestHandler;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use Slim\Http\Body;
use Slim\Http\Environment;
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Http\RequestBody;
use Slim\Http\Response;
use Slim\Http\Uri;

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

        $method = new \ReflectionMethod(SlimRequestHandler::class, 'getApp');
        $method->setAccessible(true);
        $app = $method->invoke($handler, $req);

        $app->get('/foo', function($req, $res) {
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

        $method = new \ReflectionMethod(SlimRequestHandler::class, 'getApp');
        $method->setAccessible(true);
        $app = $method->invoke($handler, $req);

        $app->get('/foo', function($req, $res) use ($response) {
            return $response;
        });
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
}
