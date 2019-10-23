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

    public function __construct(int $responseStatus = 200)
    {
        $this->responseStatus = $responseStatus;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->processedRequest = $request;
        return new Response($this->responseStatus);
    }
}
