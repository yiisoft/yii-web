<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Event;

use Psr\Http\Message\ResponseInterface;

final class AfterRequest
{
    private ?ResponseInterface $response;

    /**
     * @param ResponseInterface|null $response Response instance or null if response generation failed due to an error.
     */
    public function __construct(?ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }
}
