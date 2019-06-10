<?php

use yii\di\Reference;

return [
    'aliases' => [
        '@web' => '/',
    ],

    'app' => [
        '__class' => Yiisoft\Web\Application::class,
    ],
//    'request' => [
//        '__class' => Yiisoft\Web\Request::class,
//        'cookieValidationKey' => $params['request.cookieValidationKey'],
//    ],
//    'response' => [
//        '__class' => Yiisoft\Web\Response::class,
//        'formatters' => [
//            Yiisoft\Web\Response::FORMAT_HTML => [
//                '__class' => Yiisoft\Web\formatters\HtmlResponseFormatter::class,
//            ],
//            Yiisoft\Web\Response::FORMAT_XML => [
//                '__class' => Yiisoft\Web\formatters\XmlResponseFormatter::class,
//            ],
//            Yiisoft\Web\Response::FORMAT_JSON => [
//                '__class' => Yiisoft\Web\formatters\JsonResponseFormatter::class,
//            ],
//            Yiisoft\Web\Response::FORMAT_JSONP => [
//                '__class' => Yiisoft\Web\formatters\JsonResponseFormatter::class,
//                'useJsonp' => true,
//            ],
//        ],
//    ],
    'session' => [
        '__class' => Yiisoft\Web\Session::class,
    ],

    \Yiisoft\Web\User::class => Reference::to('user'),
    'user' => [
        '__class' => Yiisoft\Web\User::class,
    ],

    'errorHandler' => [
        '__class' => Yiisoft\Web\ErrorHandler::class,
        'errorAction' => 'site/error',
    ],
];
