<?php
declare(strict_types=1);
namespace Bnf\SlimTypo3;

use FastRoute\Dispatcher;
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
     * Cache for dispatched (preprocessed) requests
     *
     * @var \SplObjectStorage
     */
    protected $dispatchedRequests;

    /**
     * @var array
     */
    protected static $registrations = [];

    /**
     * Create new application
     *
     * @param array $settings
     */
    public function __construct(array $settings = [])
    {
        parent::__construct($settings);
        $this->dispatchedRequests = new \SplObjectStorage;
    }

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

        return true;
    }

    /**
     * Dispatch the router to find the route. Prepare the route for use.
     *
     * Cache the dispatched request as we do not want to re-dispatch
     * during canHandleRequest() and run().
     *
     * @param  ServerRequestInterface $request
     * @param  RouterInterface        $router
     * @return ServerRequestInterface
     */
    protected function dispatchRouterAndPrepareRoute(ServerRequestInterface $request, RouterInterface $router): ServerRequestInterface
    {
        if ($this->dispatchedRequests->offsetExists($request)) {
            return $this->dispatchedRequests->offsetGet($request);
        }

        $dispatchedRequest = parent::dispatchRouterAndPrepareRoute($request, $router);
        $this->dispatchedRequests->offsetSet($request, $dispatchedRequest);

        return $dispatchedRequest;
    }

    /**
     * Register a new App
     *
     * @param  callable|string $callable
     * @return void
     */
    public static function register($callable)
    {
        self::$registrations[] = $callable;
    }

    /**
     * @return array
     */
    public static function getRegistrations()
    {
        /* @TODO: Add a hook to modify registrations? */
        return self::$registrations;
    }
}
