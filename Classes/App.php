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
     * Cache for preprocessed (dispatched) requests
     *
     * @var \SplObjectStorage
     */
    protected $requests = null;

    /**
     * Create new application
     *
     * @param array $settings
     */
    public function __construct(array $settings = [])
    {
        parent::__construct($settings);
        $this->requests = new \SplObjectStorage;
    }

    /**
     * @return bool If a route matches the request, TRUE otherwise FALSE
     */
    public function canHandleRequest(): bool
    {
        $router = $this->getContainer()->get('router');
        $request = $this->getContainer()->get('request');

        $processedRequest = $this->dispatchRouterAndPrepareRoute($request, $router);
        $routeInfo = $processedRequest->getAttribute('routeInfo');

        switch ($routeInfo[RouterInterface::DISPATCH_STATUS]) {
        case Dispatcher::NOT_FOUND:
            return false;
        }

        $this->requests->offsetSet($request, $processedRequest);

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
        if ($this->requests->offsetExists($request)) {
            $request = $this->requests->offsetGet($request);
        }

        return parent::process($request, $response);
    }
}
