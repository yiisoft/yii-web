<?php

use yii\di\Reference;

return [
    'app' => [
        '__class' => yii\web\Application::class,
        'aliases' => [
            '@web' => '/',
        ],
    ],

    'assetManager' => [
        '__class'   => yii\web\AssetManager::class,
        'basePath'  => '@public/assets',
        'baseUrl'   => '@web/assets',
    ],
    'urlManager' => [
        '__class' => yii\web\UrlManager::class,
    ],
    'urlNormalizer' => [
        '__class' => yii\web\UrlNormalizer::class,
    ],
    'view' => [
        '__class' => yii\web\View::class,
    ],
    'request' => [
        '__class' => yii\web\Request::class,
        'cookieValidationKey' => $params['request.cookieValidationKey'],
    ],
    'response' => [
        '__class' => yii\web\Response::class,
        'formatters' => [
            yii\web\Response::FORMAT_HTML => [
                '__class' => yii\web\formatters\HtmlResponseFormatter::class,
            ],
            yii\web\Response::FORMAT_XML => [
                '__class' => yii\web\formatters\XmlResponseFormatter::class,
            ],
            yii\web\Response::FORMAT_JSON => [
                '__class' => yii\web\formatters\JsonResponseFormatter::class,
            ],
            yii\web\Response::FORMAT_JSONP => [
                '__class' => yii\web\formatters\JsonResponseFormatter::class,
                'useJsonp' => true,
            ],
        ],
    ],
    'session' => [
        '__class' => yii\web\Session::class,
    ],

    \yii\web\User::class => \yii\di\Reference::to('user'),
    'user' => [
        '__class' => yii\web\User::class,
    ],

    'errorHandler' => [
        '__class' => yii\web\ErrorHandler::class,
        'errorAction' => 'site/error',
    ],

    'assetManager' => [
        '__class' => yii\web\AssetManager::class,
    ],

    /// TODO: move to swiftmailer
    'mailer' => [
        '__class' => yii\swiftmailer\Mailer::class,
    ],
];
