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

    // set to 'prepend' if you need support for \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump()
    // within slim routes
    'outputBuffering' => false,

    //'routerCacheFile' => PATH_site . 'typo3temp/Cache/Code/slim_typo3/router-cache.php',
];

\Bnf\SlimTypo3\App::register(\Bnf\SlimTypo3\Example\TestApp::class);

\Bnf\SlimTypo3\App::register(function (\Slim\App $app) {
    $app->get('/hello/{name}', function (\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Message\ResponseInterface $response): \Psr\Http\Message\ResponseInterface {
        $response->getBody()->write('Hello ' . htmlspecialchars($request->getAttribute('name')));
        return $response;
    });
});
