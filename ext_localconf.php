<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

if (TYPO3_REQUESTTYPE & (TYPO3_REQUESTTYPE_FE | TYPO3_REQUESTTYPE_BE | TYPO3_REQUESTTYPE_AJAX)) {
    \TYPO3\CMS\Core\Core\Bootstrap::getInstance()->registerRequestHandlerImplementation(
        \Bnf\SlimTypo3\Http\SlimRequestHandler::class
    );
}

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['slim_typo3']['settings'] = [
    'displayErrorDetails' => (int)$GLOBALS['TYPO3_CONF_VARS']['SYS']['displayErrors'] === 1,
    //'routerCacheFile' => PATH_site . 'typo3temp/Cache/Code/slim_typo3/router-cache.php',

    // set to 'prepend' if you need support for \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump()
    // within slim routes
    'outputBuffering' => false,
];

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['slim_typo3']['configureApp'][] = \Bnf\SlimTypo3\Example\TestApp::class;

/* TODO: provide an easy to use route/middleware-add interface like (to be used instead of the 'configureApp' Hook:
\Bnf\SlimTypo3\NameThis::configure(function (\Slim\App $app) {
    $app->get('/test', \Bnf\SlimTypo3\Example\TestApp::class . ':bar');
});

Or \Bnf\SlimTypo3\Http\SlimRequestHandler::configure(function (\Slim\App $app) {}); ?
*/
