<?php
declare(strict_types=1);
namespace Bnf\SlimTypo3\Http;

use Bnf\SlimTypo3\AppRegistry;
use Bnf\SlimTypo3\CallableResolver;
use Bnf\SlimTypo3\Container\DelegatingPimple;
use Bnf\SlimTypo3\Container\MergingContainer;
use Bnf\SlimTypo3\Container\ObjectManagerAdapter;
use FastRoute\Dispatcher;
use Pimple\Container as Pimple;
use Pimple\ServiceProviderInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\App;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\RouterInterface;
use Slim\Router;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @author Benjamin Franzke <bfr@qbus.de>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class SlimMiddleware implements MiddlewareInterface, RequestHandlerInterface, ServiceProviderInterface
{
    /**
     * @var ContainerInterface
     */
    protected $rootContainer;

    /**
     * @var \SplObjectStorage
     */
    protected $containers;

    /**
     */
    public function __construct(ContainerInterface $rootContainer = null)
    {
        $this->rootContainer = $rootContainer ?? new ObjectManagerAdapter(
            // Fall back to Extbase ObjectManager DependencyInjection for TYPO3 v9
            GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class)
        );

        $this->containers = GeneralUtility::makeInstance(\SplObjectStorage::class);

        /* Load FastRoute functions in non-composer (classic) mode. */
        function_exists('FastRoute\\simpleDispatcher') || include __DIR__ . '/../../Resources/Private/PHP/nikic/fast-route/src/functions.php';
    }

    /**
     * @param  ServerRequestInterface $request
     * @return ContainerInterface
     */
    protected function getContainer(ServerRequestInterface $request): ContainerInterface
    {
        if ($this->containers->offsetExists($request)) {
            return $this->containers->offsetGet($request);
        }

        $container = new MergingContainer;
        $pimple = GeneralUtility::makeInstance(DelegatingPimple::class, ['psr11-container' => $container], $container);
        $container->add($pimple);
        $container->add($this->rootContainer);

        $pimple->register($this);
        foreach (GeneralUtility::makeInstance(AppRegistry::class) as $possibleServiceProvider) {
            $instance = $possibleServiceProvider;
            if (is_string($possibleServiceProvider) && class_exists($possibleServiceProvider)) {
                $instance = GeneralUtility::makeInstance($possibleServiceProvider);
            }
            if ($instance instanceof ServiceProviderInterface) {
                $pimple->register($instance);
            }
        }

        $this->containers->offsetSet($request, $container);

        return $container;
    }

    /**
     * Method implementing Pimple's ServiceProviderInterface
     *
     * Registers containers
     *
     * @param Pimple $container
     */
    public function register(Pimple $container)
    {
        if (!isset($container['pimple'])) {
            /* Provide access to pimple from the psr11-container wrapper */
            $container['pimple'] = $container;
        }

        if (!isset($container['settings'])) {
            $container['settings'] = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['slim_typo3']['settings'];
        }

        if (!isset($container['callableResolver'])) {
            $container['callableResolver'] = function (Pimple $container): CallableResolverInterface {
                return GeneralUtility::makeInstance(CallableResolver::class, $container['psr11-container']);
            };
        }

        if (!isset($container['response'])) {
            $container['response'] = function (Pimple $container): ResponseInterface {
                $headers = ['Content-Type' => 'text/html; charset=UTF-8'];
                $response = GeneralUtility::makeInstance(Response::class, 'php://temp', 200, $headers);
                $response = $response->withProtocolVersion($container['settings']['httpVersion']);

                return $response;
            };
        }

        if (!isset($container['registrations'])) {
            $container['registrations'] = function (Pimple $container): AppRegistry {
                return GeneralUtility::makeInstance(AppRegistry::class);
            };
        }

        if (!isset($container['app'])) {
            $container['app'] = function (Pimple $container): App {
                $container = $container['psr11-container'];

                $app = GeneralUtility::makeInstance(App::class, $container);

                /**
                 * @TODO: Create separate containers for every registration?
                 * How likely is it, that multiple extensions want to
                 * modify the same App? Middleware's will probably conflict?
                 */
                foreach ($container->get('registrations') as $callable) {
                    $callable = $container->get('callableResolver')->resolve($callable);
                    call_user_func($callable, $app);
                }

                return $app;
            };
        }

        $defaultServices = new \ArrayObject;
        GeneralUtility::makeInstance(\Slim\DefaultServicesProvider::class)->register($defaultServices);
        foreach ($defaultServices as $service => $callable) {
            if (!isset($container[$service])) {
                $container[$service] = function (Pimple $container) use ($callable) {
                    return $callable($container['psr11-container']);
                };
            }
        }
    }

    /*
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $nextHandler): ResponseInterface
    {
        if ($this->canHandleRequest($request)) {
            return $this->handle($request);
        }

        return $nextHandler->handle($request);
    }

    /**
     * Handles a frontend request
     *
     * @param  ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $container = $this->getContainer($request);

        $app = $container->get('app');
        if ($container->has('preprocessed-request')) {
            $request = $container->get('preprocessed-request');
        }

        /* Instruct slim (v3 only) to use our $request */
        $container->get('pimple')->offsetSet('request', $request);

        /* TODO: Use $app->process() as is v4? */
        return $app->run(true);
    }

    /**
     * The Slim RequestHandler can handle the request if
     * a matching route is found.
     *
     * @param  ServerRequestInterface $request
     * @return bool                   If the slim app has a matching route, TRUE otherwise FALSE
     */
    protected function canHandleRequest(ServerRequestInterface $request): bool
    {
        $container = $this->getContainer($request);

        /* Create the application, this will implicitly create an instance of \Slim\App
         * and initialize using the AppRegistry */
        $container->get('app');

        $router = $container->get('router');

        $preprocessedRequest = $this->performRouting($request, $router);
        $container->get('pimple')->offsetSet('preprocessed-request', $preprocessedRequest);

        $routeInfo = $preprocessedRequest->getAttribute('routeInfo');
        switch ($routeInfo[RouterInterface::DISPATCH_STATUS]) {
        case Dispatcher::NOT_FOUND:
            return false;
        }

        return true;
    }

    /**
     * Copied from the upcoming (4.x) RoutingMiddleware
     *
     * @param  ServerRequestInterface $request
     * @param  RouterInterface        $router
     * @return ServerRequestInterface
     */
    private function performRouting(ServerRequestInterface $request, RouterInterface $router): ServerRequestInterface
    {
        $routeInfo = $router->dispatch($request);
        if ($routeInfo[0] === Dispatcher::FOUND) {
            $routeArguments = [];
            foreach ($routeInfo[2] as $k => $v) {
                $routeArguments[$k] = urldecode($v);
            }
            $route = $router->lookupRoute($routeInfo[1]);
            $route->prepare($request, $routeArguments);
            // add route to the request's attributes
            $request = $request->withAttribute('route', $route);
        }
        // routeInfo to the request's attributes
        $routeInfo['request'] = [$request->getMethod(), (string) $request->getUri()];

        return $request->withAttribute('routeInfo', $routeInfo);
    }
}
