<?php
declare(strict_types=1);
namespace Bnf\SlimTypo3;

use FastRoute\Dispatcher;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\RouterInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
     * Create new application
     *
     * @param array $settings
     */
    public function __construct(array $settings = [])
    {
        /* Load FastRoute functions in non-composer (classic) mode. */
        function_exists('FastRoute\\simpleDispatcher') || include __DIR__ . '/../Resources/Private/PHP/nikic/fast-route/src/functions.php';

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
     * @deprecated since 0.2.0, will be removed when leaving 0.x
     */
    public static function register($callable)
    {
        GeneralUtility::deprecationLog(self::class . '::register() is deprecated. Will be supported only for 0.x releases. And removed with the next major versio number.');
        GeneralUtility::makeInstance(AppRegistry::class)->push($callable);
    }
}
