<?php

return [
    'frontend' => [
        'jv/jvchat/ajax' => [
            'target' => \JV\Jvchat\Middleware\Ajax::class,
            'after' => [
                'typo3/cms-frontend/content-length-headers'
            ],
        ],
    ],
];