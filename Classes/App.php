<?php
declare(strict_types=1);
namespace Bnf\SlimTypo3;

use FastRoute\Dispatcher;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\RouterInterface;

/**
 * App
 *
 * @author Benjamin Franzke <bfr@qbus.de>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class App extends \Slim\App
{
    /**
     * @TODO: move into a SplObjectStorage? That way we ensure the process()
     * overlay doesn't look so ugly (ignoring the $request parameter)
     *
     * @var ServerRequestInterface
     */
    protected $preProcessedRequest = null;

    /**
     * @return bool If a route matches the request, TRUE otherwise FALSE
     */
    public function canHandleRequest(): bool
    {
        $router = $this->getContainer()->get('router');
        $request = $this->getContainer()->get('request');

        $request = $this->dispatchRouterAndPrepareRoute($request, $router);
        $routeInfo = $request->getAttribute('routeInfo');

        switch ($routeInfo[RouterInterface::DISPATCH_STATUS]) {
        case Dispatcher::NOT_FOUND:
            return false;
        }

        /* @TODO: Can we simply do $container['request'] = $request; ? */
        $this->preProcessedRequest = $request;

        return true;
    }

    /**
     * Process a request
     *
     * This method traverses the application middleware stack and then returns the
     * resultant Response object.
     *
     * @param  ServerRequestInterface $request
     * @param  ResponseInterface      $response
     * @return ResponseInterface
     *
     * @throws Exception
     * @throws MethodNotAllowedException
     * @throws NotFoundException
     */
    public function process(ServerRequestInterface $request, ResponseInterface $response) : ResponseInterface
    {
        if ($this->preProcessedRequest !== null) {
            $request = $this->preProcessedRequest;
        }

        return parent::process($request, $response);
    }
}
