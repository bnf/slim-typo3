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
     * @var AppRegistry
     */
    protected $appRegistry;

    /**
     */
    public function __construct(ContainerInterface $rootContainer = null, AppRegistry $appRegistry = null)
    {
        $this->rootContainer = $rootContainer ?? new ObjectManagerAdapter(
            // Fall back to Extbase ObjectManager DependencyInjection for TYPO3 v9
            GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class)
        );
        $this->appRegistry = $appRegistry ?? GeneralUtility::makeInstance(AppRegistry::class);

        /* Load FastRoute functions in non-composer (classic) mode. */
        function_exists('FastRoute\\simpleDispatcher') || include __DIR__ . '/../../Resources/Private/PHP/nikic/fast-route/src/functions.php';
    }

    /**
     * @param  ServerRequestInterface $request
     * @return ContainerInterface
     */
    protected function getContainer(ServerRequestInterface $request): ContainerInterface
    {
        $container = new MergingContainer;
        $defaultServices = [
            'psr11-container' => $container,
            'registrations' => $this->appRegistry,
            'request' => $request,
        ];
        $pimple = new DelegatingPimple($defaultServices, $container);
        $container->add($pimple);
        $container->add($this->rootContainer);

        $pimple->register($this);
        foreach ($this->appRegistry as $possibleServiceProvider) {
            $instance = $possibleServiceProvider;
            if (is_string($possibleServiceProvider) && class_exists($possibleServiceProvider)) {
                $instance = GeneralUtility::makeInstance($possibleServiceProvider);
            }
            if ($instance instanceof ServiceProviderInterface) {
                $pimple->register($instance);
            }
        }

        return $container;
    }

    /**
     * Method implementing Pimple's ServiceProviderInterface
     *
     * Registers containers
     *
     * @param Pimple $container
     * @return void
     */
    public function register(Pimple $container): void
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

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $nextHandler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $nextHandler): ResponseInterface
    {
        $container = $this->getContainer($request);

        /* Create the application, this will implicitly create an instance of \Slim\App
         * and initialize using the AppRegistry */
        $container->get('app');

        $router = $container->get('router');
        $preprocessedRequest = $this->performRouting($request, $router);

        $routeInfo = $preprocessedRequest->getAttribute('routeInfo');
        switch ($routeInfo[RouterInterface::DISPATCH_STATUS]) {
        case Dispatcher::NOT_FOUND:
            return $nextHandler->handle($request);
        }

        return $this->doHandle($container, $preprocessedRequest);
    }

    /**
     * Handles a frontend request
     *
     * @param  ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->doHandle($this->getContainer($request), $request);
    }

    /**
     * Handles a frontend request
     *
     * @param  ContainerInterface     $container
     * @param  ServerRequestInterface $request
     * @return ResponseInterface
     */
    private function doHandle(ContainerInterface $container, ServerRequestInterface $request): ResponseInterface
    {
        /* Instruct slim (v3 only) to use our $request */
        $container->get('pimple')->offsetSet('request', $request);

        /* TODO: Use $app->process() as is v4? */
        return $container->get('app')->run(true);
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
