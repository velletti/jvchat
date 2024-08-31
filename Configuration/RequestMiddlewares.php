<?php

use JV\Jvchat\Middleware\Ajax;
use JVelletti\JvTyposcript\Middleware\Typoscript;
return [
    'frontend' => [
        'jv/jvchat/ajax' => [
            'target' => Ajax::class,
            'after' => [
                'typo3/cms-frontend/content-length-headers'
            ],
        ],
    ],
];
