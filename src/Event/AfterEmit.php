<?php

namespace Yiisoft\Yii\Web\Event;

use Psr\Http\Message\ResponseInterface;

final class AfterEmit
{
    private ResponseInterface $response;

    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
