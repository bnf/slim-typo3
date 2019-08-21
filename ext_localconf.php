<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['slim_typo3']['settings'] = [
    'displayErrorDetails' => (int)$GLOBALS['TYPO3_CONF_VARS']['SYS']['displayErrors'] === 1,

    // set to 'prepend' if you need support for \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump()
    // within slim routes
    'outputBuffering' => false,

    //'routerCacheFile' => PATH_site . 'typo3temp/Cache/Code/slim_typo3/router-cache.php',
];

if (version_compare(TYPO3_version, '10.0', '<')) {
    $container = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\Container\Container::class);
    $container->registerImplementation(\Psr\Http\Message\RequestFactoryInterface::class, \Bnf\Typo3HttpFactory\RequestFactory::class);
    $container->registerImplementation(\Psr\Http\Message\ResponseFactoryInterface::class, \Bnf\Typo3HttpFactory\ResponseFactory::class);
    $container->registerImplementation(\Psr\Http\Message\ServerRequestFactoryInterface::class, \Bnf\Typo3HttpFactory\ServerRequestFactory::class);
    $container->registerImplementation(\Psr\Http\Message\StreamFactoryInterface::class, \Bnf\Typo3HttpFactory\StreamFactory::class);
    $container->registerImplementation(\Psr\Http\Message\UploadedFileFactoryInterface::class, \Bnf\Typo3HttpFactory\UploadedFileFactory::class);
    $container->registerImplementation(\Psr\Http\Message\UriFactoryInterface::class, \Bnf\Typo3HttpFactory\UriFactory::class);
    unset($container);
}
