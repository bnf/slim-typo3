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
    //'routerCacheFile' => PATH_site . 'typo3temp/var/transient/slim-typo3-router-cache.php',
    //'determineRouteBeforeAppMiddleware' => true,
    /* As far as i can see output buffering is pure convenience in slim:
     * https://github.com/slimphp/Slim/commit/2f02b4ba
     * let's disable it. Also it'll probably be a (non-default) middleware in future */
    //'outputBuffering' => false,

    // 'outputBuffering' => 'prepend' – may be needed to support sth like \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump() in routes/middleware
    'outputBuffering' => 'prepend',
];

// @TODO: use
//        $productionExceptionHandlerClassName = $GLOBALS['TYPO3_CONF_VARS']['SYS']['productionExceptionHandler'];
//        $debugExceptionHandlerClassName = $GLOBALS['TYPO3_CONF_VARS']['SYS']['debugExceptionHandler'];
//
//        $errorHandlerClassName = $GLOBALS['TYPO3_CONF_VARS']['SYS']['errorHandler'];
//        $errorHandlerErrors = $GLOBALS['TYPO3_CONF_VARS']['SYS']['errorHandlerErrors'];
//
//

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['slim_typo3']['configureApp'][] = \Bnf\SlimTypo3\Example\TestApp::class;
