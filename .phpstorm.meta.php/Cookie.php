<?php

namespace PHPSTORM_META {

    expectedArguments(
        \Yiisoft\Yii\Web\Cookie::sameSite(),
        0,
        argumentsSet('yiisoft/yii-web/cookie'),
    );

    registerArgumentsSet(
        'yiisoft/yii-web/cookie',
        \Yiisoft\Yii\Web\Cookie::SAME_SITE_LAX,
        \Yiisoft\Yii\Web\Cookie::SAME_SITE_STRICT,
        \Yiisoft\Yii\Web\Cookie::SAME_SITE_NONE,
    );
}
