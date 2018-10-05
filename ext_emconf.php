<?php

$EM_CONF[$_EXTKEY] = array(
    'title' => 'Slim Framework integration',
    'description' => 'Integrates the Slim microframework. Providing a PSR-7 based router and middleware in front of TYPO3 (executed when a route matches)',
    'category' => '',
    'author' => 'Benjamin Franzke',
    'author_email' => 'benjaminfranzke@gmail.com',
    'state' => 'stable',
    'internal' => '',
    'uploadfolder' => '0',
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'version' => '0.3.1',
    'constraints' => array(
        'depends' => array(
            'typo3' => '9.5.0-9.5.99',
        ),
        'conflicts' => array(
        ),
        'suggests' => array(
        ),
    ),
    'autoload' => array(
        'psr-4' => array(
            'Bnf\\SlimTypo3\\' => 'Classes',

            'Slim\\' => 'Resources/Private/PHP/slim/slim/Slim',
            'Bnf\\Slim3Psr15\\' => 'Resources/Private/PHP/bnf/slim3-psr15/src',
            'FastRoute\\' => 'Resources/Private/PHP/nikic/fast-route/src',
            'Bnf\\Typo3Middleware\\' => 'Resources/Private/PHP/bnf/typo3-middleware/src',
        ),
        'classmap' => array(
            'Resources/Private/PHP/pimple/pimple/src/Pimple/'
        ),
    ),
);
