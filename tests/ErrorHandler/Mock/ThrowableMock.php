<?php


namespace Yiisoft\Yii\Web\Tests\ErrorHandler\Mock;


final class ThrowableMock extends \Exception
{
    public static function newInstance(array $params): self {
        return new self($params['message'] ?? '', $params['code'] ?? 0, $params['previous'] ?? null);
    }
}
