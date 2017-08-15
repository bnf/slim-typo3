<?php
declare(strict_types=1);
namespace Bnf\SlimTypo3\Example;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * TestApp
 *
 * @author Benjamin Franzke <bfr@qbus.de>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class TestApp implements SingletonInterface
{
    /**
     * @param  App  $app
     * @return void
     */
    public function __invoke(App $app)
    {
        $app->add(function (Request $request, Response $response, callable $next) {
            $response->getBody()->write('I am middleware, adding content before.<br>');
            $response = $next($request, $response, $next);
            $response->getBody()->write('<br>..and now after the content.');

            return $response;
        });

        $app->get('/bar/', static::class . ':bar');
        $app->get('/bar[/{foo}]', static::class . ':bar');

        /* Api Example */
        $app->group('/api', function () {
            /* TYPO3 callable syntax using '->' */
            $this->get('/foo', 'Bnf\\SlimTypo3\\Example\\TestApp->bar');

            /* Catchall rule to ensure all requests to /api* are handling by this App */
            $this->any('{catchall:.*}', function (Request $request, Response $response): Response {
                return $response->withStatus(404);
            });
        });

        // You can even add backend routes. HEADS UP! this performs no authentication/authorization.
        $app->get('/typo3/foo[/{foo}]', static::class . ':bar');

        /* You probably don't want this. '/' should probably be handled
         * by typo3 to show your homepage. */
        //$app->get('/', function ($request, $response) {
        //    $response->getBody()->write('root');
        //    return $response;
        //});
    }

    public function bar(Request $request, Response $response): Response
    {
        $response->getBody()->write('baz');

        $foo = $request->getAttribute('foo');
        if ($foo) {
            $response->getBody()->write(' ' . htmlspecialchars($foo));
        }

        return $response;
    }
}
