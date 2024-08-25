<?php

use JV\Jvchat\Middleware\Ajax;
use JVelletti\JvTyposcript\Middleware\Typoscript ;
return [
    'frontend' => [
        'jv/jvchat/ajax' => [
            'target' => Ajax::class,
            'after' => [
                'typo3/cms-frontend/content-length-headers'
            ],
        ],
        'jvelletti/jv-typoscript/typoscript' => [
            'target' => Typoscript::class,
            'after' => [
                'typo3/cms-frontend/prepare-tsfe-rendering'
            ],
            'before' => [
                'typo3/cms-frontend/content-length-headers'
            ]
        ],
    ],
];
