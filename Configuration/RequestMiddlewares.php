<?php

use JVelletti\Jvchat\Middleware\Ajax;
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
