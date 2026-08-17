<?php

return [

    /*---------------------------------------------
    | Kernel-level Global Middleware
    | Runs before route matching.
    | Keep these middleware stateless.
    | Session is not configured at this stage.
    ---------------------------------------------*/

    'kernel' => [
        'web' => [
            \App\Middlewares\WebHeaders::class,
        ],

        'api' => [
            \App\Middlewares\ApiHeaders::class,
        ]
    ],

    /*---------------------------------------------
    | Route-level global Middleware
    ---------------------------------------------*/

    'route' => [

        'web' => [
            \App\Middlewares\RateLimit::class,
            \App\Middlewares\RememberMe::class,
            \App\Middlewares\Csrf::class,
        ],

        'api' => [
            \App\Middlewares\RateLimit::class,
        ]
    ],
];
