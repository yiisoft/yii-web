<?php

return [
    'aliases' => [
        // @root needs to be redefined in the application config
        '@root' => dirname(__DIR__),
        '@vendor' => '@root/vendor',
        '@public' => '@root/public',
        '@runtime' => '@root/runtime',
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
        '@web' => '/',
    ]
];
