<?php

namespace PHPSTORM_META {

    expectedArguments(
        \Yiisoft\Yii\Web\Cookie::sameSite(),
        0,
        argumentsSet('\Yiisoft\Yii\Web\Cookie::SAME_SITE'),
    );

    registerArgumentsSet(
        '\Yiisoft\Yii\Web\Cookie::SAME_SITE',
        \Yiisoft\Yii\Web\Cookie::SAME_SITE_LAX,
        \Yiisoft\Yii\Web\Cookie::SAME_SITE_STRICT,
        \Yiisoft\Yii\Web\Cookie::SAME_SITE_NONE,
    );
}
