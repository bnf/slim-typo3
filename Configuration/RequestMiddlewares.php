<?php

/**
 * Definitions for middlewares provided by EXT:redirects
 */
return [
    'frontend' => [
        'bnf/slim-typo3/slim-middleware' => [
            'target' => \Bnf\SlimTypo3\Http\SlimMiddleware::class,
            'before' => [
                'typo3/cms-frontend/timetracker',
                'typo3/cms-frontend/preprocessing',
                'typo3/cms-frontend/eid',
                'typo3/cms-frontend/tsfe',
            ],
        ],
    ],
    'backend' => [
        'bnf/slim-typo3/slim-middleware' => [
            'target' => \Bnf\SlimTypo3\Http\SlimMiddleware::class,
            'before' => [
                'typo3/cms-core/normalized-params-attribute',
                'typo3/cms-backend/locked-backend',
            ],
        ],
    ],
];
