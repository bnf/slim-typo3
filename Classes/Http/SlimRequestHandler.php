<?php
declare(strict_types=1);
namespace Bnf\SlimTypo3\Http;

use Bnf\SlimTypo3\App;
use Bnf\SlimTypo3\CallableResolver;
use Bnf\SlimTypo3\Hook\ConfigureAppHookInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\CallableResolverInterface;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Http\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * SlimRequestHandler
 *
 * @author Benjamin Franzke <bfr@qbus.de>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class SlimRequestHandler implements RequestHandlerInterface
{
    /**
     * Instance of the current TYPO3 bootstrap
     * @var Bootstrap
     */
    protected $bootstrap;

    /**
     * @var \SplObjectStorage
     */
    protected $apps;

    /**
     * Constructor handing over the bootstrap
     *
     * @param Bootstrap $bootstrap
     */
    public function __construct(Bootstrap $bootstrap)
    {
        $this->bootstrap = $bootstrap;
        $this->apps = new \SplObjectStorage;
    }

    /**
     * @param  ServerRequestInterface $request
     * @return App
     */
    protected function getApp(ServerRequestInterface $request): App
    {
        if ($this->apps->offsetExists($request)) {
            return $this->apps->offsetGet($request);
        }

        $container = $this->prepareContainer($request);
        $app = GeneralUtility::makeInstance(App::class, $container);

        /**
         * @TODO: Create separate Apps for every registration?
         * How likely is it, that multiple extensions want to
         * modify the same App? Middleware's will probably conflict?
         */
        /**
         * @TODO: Use a stack for the registrations
         * and thus call them in reverse order?
         * That'd allow extensions that depend on others
         * to add outer middlewares (which is probably
         * the desired thing).
         */
        foreach (App::getRegistrations() as $callable) {
            $callable = $app->getContainer()->get('callableResolver')->resolve($callable);
            call_user_func($callable, $app);
        }

        /**
         * @TODO: Remove this hook?
         */
        if (isset($container['configureApp']) && is_array($container['configureApp'])) {
            foreach ($container['configureApp'] as $classRef) {
                if (!class_exists($classRef) || !in_array(ConfigureAppHookInterface::class, class_implements($classRef))) {
                    throw new \UnexpectedValueException($classRef . ' must implement interface ' . ConfigureAppHookInterface::class, 1502655884);
                }
                call_user_func($classRef . '::configure', $app);
            }
        }

        $this->apps->offsetSet($request, $app);

        return $app;
    }

    /**
     * @param  ServerRequestInterface $request
     * @return array
     */
    protected function prepareContainer(ServerRequestInterface $request)
    {
        $container = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['slim_typo3'];

        /**
         * Add the $request that we retrieve from \TYPO3\CMS\Core\Core\Bootstrap
         * to the container. This will instruct \Slim\App to use this request
         * when parsing routes
         *
         * @var ServerRequestInterface
         */
        $container['request'] = $request;

        if (!isset($container['response'])) {
            /**
             * PSR-7 Response object
             *
             * @param Container $container
             *
             * @return ResponseInterface
             */
            $container['response'] = function (ContainerInterface $container): ResponseInterface {
                $headers = ['Content-Type' => 'text/html; charset=UTF-8'];
                $response = GeneralUtility::makeInstance(Response::class, 'php://temp', 200, $headers);

                return $response->withProtocolVersion($container->get('settings')['httpVersion']);
            };
        }

        if (!isset($container['callableResolver'])) {
            /**
             * Instance of \Slim\Interfaces\CallableResolverInterface
             *
             * @param ContainerInterface $container
             *
             * @return CallableResolverInterface
             */
            $container['callableResolver'] = function (ContainerInterface $container): CallableResolverInterface {
                return GeneralUtility::makeInstance(CallableResolver::class, $container);
            };
        }

        return $container;
    }

    /**
     * Handles a frontend request
     *
     * @param  ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        return $this->getApp($request)->run(true);
    }

    /**
     * The Slim RequestHandler can handle the request if
     * a matching route is found.
     *
     * @param  ServerRequestInterface $request
     * @return bool                   If the slim app has a matching route, TRUE otherwise FALSE
     */
    public function canHandleRequest(ServerRequestInterface $request): bool
    {
        return $this->getApp($request)->canHandleRequest();
    }

    /**
     * Returns the priority - how eager the handler is to actually handle the
     * request.
     *
     * @return int The priority of the request handler.
     */
    public function getPriority(): int
    {
        return 75;
    }
}
