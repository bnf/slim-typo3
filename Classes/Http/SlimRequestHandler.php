<?php
namespace Bnf\SlimTypo3\Http;

use Bnf\SlimTypo3\App;
use Bnf\SlimTypo3\Hook\ConfigureAppHookInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Http\RequestHandlerInterface;
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
     * Constructor handing over the bootstrap and the original request
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
     * @return void
     */
    protected function getApp(ServerRequestInterface $request)
    {
        if ($this->apps->offsetExists($request)) {
            return $this->apps->offsetGet($request);
        }

        $container = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['slim_typo3'];
        $container['request'] = $request;

        $app = GeneralUtility::makeInstance(App::class, $container);

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
     * Handles a frontend request
     *
     * @param  ServerRequestInterface                   $request
     * @return NULL|\Psr\Http\Message\ResponseInterface
     */
    public function handleRequest(ServerRequestInterface $request)
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
    public function canHandleRequest(ServerRequestInterface $request)
    {
        return $this->getApp($request)->canHandleRequest();
    }

    /**
     * Returns the priority - how eager the handler is to actually handle the
     * request.
     *
     * @return int The priority of the request handler.
     */
    public function getPriority()
    {
        return 75;
    }
}
