<?php


namespace Yiisoft\Yii\Web\Tests\Middleware\Mock;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MockRequestHandler implements RequestHandlerInterface
{
    /**
     * @var ServerRequestInterface
     */
    public $processedRequest;

    /**
     * @var int
     */
    private $responseStatus;

    /**
     * @var \Throwable|null
     */
    private $handleException;

    public function __construct(int $responseStatus = 200)
    {
        $this->responseStatus = $responseStatus;
    }

    /**
     * @return static
     */
    public function setHandleExcaption(?\Throwable $throwable) {
        $this->handleException = $throwable;
        return $this;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if($this->handleException !== null) {
            throw $this->handleException;
        }
        $this->processedRequest = $request;
        return new Response($this->responseStatus);
    }
}
