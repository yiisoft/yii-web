<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\web\tests\filters\stubs;

use yii\helpers\Yii;
use yii\rbac\PhpManager;
use yii\rbac\RuleFactory;

class MockAuthManager extends PhpManager
{
    public function __construct()
    {
        parent::__construct(Yii::getAlias('@runtime/tests-auth'), new RuleFactory());
    }

    /**
     * This mock does not persist.
     * {@inheritdoc}
     */
    protected function saveToFile($data, $file)
    {
    }
}
