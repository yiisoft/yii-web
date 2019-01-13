<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\web\tests\stubs;

use yii\web\UrlManager;
use yii\web\UrlRule;

class CachedUrlRule extends UrlRule
{
    public $createCounter = 0;

    public function createUrl(UrlManager $manager, string $route, array $params)
    {
        $this->createCounter++;
        return parent::createUrl($manager, $route, $params);
    }
}
