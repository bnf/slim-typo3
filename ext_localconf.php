<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

if (version_compare(TYPO3_version, '9.4', '<')) {
    if (!(TYPO3_REQUESTTYPE & (TYPO3_REQUESTTYPE_CLI | TYPO3_REQUESTTYPE_INSTALL))) {
        \TYPO3\CMS\Core\Core\Bootstrap::getInstance()->registerRequestHandlerImplementation(
            \Bnf\SlimTypo3\Http\SlimRequestHandler::class
        );
    }
}

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['slim_typo3']['settings'] = [
    'displayErrorDetails' => (int)$GLOBALS['TYPO3_CONF_VARS']['SYS']['displayErrors'] === 1,

    // set to 'prepend' if you need support for \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump()
    // within slim routes
    'outputBuffering' => false,

    //'routerCacheFile' => PATH_site . 'typo3temp/Cache/Code/slim_typo3/router-cache.php',
];
