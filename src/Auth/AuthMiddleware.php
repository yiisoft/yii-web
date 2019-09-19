<?php
namespace Yiisoft\Yii\Web\Auth;


use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Strings\StringHelper;

final class AuthMiddleware implements MiddlewareInterface
{
    private const REQUEST_NAME = 'user';

    private $requestName = self::REQUEST_NAME;
    private $responseFactory;
    private $authenticator;
    private $optional = [];

    public function __construct(ResponseFactoryInterface $responseFactory, AuthInterface $authenticator)
    {
        $this->responseFactory = $responseFactory;
        $this->authenticator = $authenticator;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->isOptional($request)) {
            return $handler->handle($request);
        }
        $identity = $this->authenticator->authenticate($request);

        if ($identity === null) {
            $response = $this->responseFactory->createResponse(401);
            $response = $this->authenticator->challenge($response);
            $response->getBody()->write('Your request was made with invalid credentials.');

            return $response;
        }

        $request->withAttribute($this->requestName, $identity);

        return $handler->handle($request);
    }

    public function setRequestName($name): void
    {
        $this->requestName = $name;
    }

    public function setOptional(array $optional): void
    {
        $this->optional = $optional;
    }

    /**
     * Checks, whether authentication is optional for the given action.
     */
    private function isOptional(ServerRequestInterface $request): bool
    {
        $path = $request->getUri()->getPath();
        foreach ($this->optional as $pattern) {
            if (StringHelper::matchWildcard($pattern, $path)) {
                return true;
            }
        }

        return false;
    }
}