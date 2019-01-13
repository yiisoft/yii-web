<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\web\tests\filters\stubs;

use yii\base\Action;
use yii\base\BaseObject;
use yii\web\filters\RateLimitInterface;
use yii\web\Request;

class RateLimit extends BaseObject implements RateLimitInterface
{
    private $_rateLimit;

    private $_allowance;

    public function getRateLimit(Request $request, Action $action): array
    {
        return $this->_rateLimit;
    }

    public function setRateLimit(array $rateLimit): self
    {
        $this->_rateLimit = $rateLimit;

        return $this;
    }

    public function loadAllowance(Request $request, Action $action): array
    {
        return $this->_allowance;
    }

    public function setAllowance(array $allowance): self
    {
        $this->_allowance = $allowance;

        return $this;
    }


    public function saveAllowance(Request $request, Action $action, int $allowance, int $timestamp)
    {
        return [$action, $allowance, $timestamp];
    }
}
