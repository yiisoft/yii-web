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
    private const REQUEST_NAME = 'auth_user';

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
        $identity = $this->authenticator->authenticate($request);
        $request = $request->withAttribute($this->requestName, $identity);

        if ($identity === null && !$this->isOptional($request)) {
            $response = $this->responseFactory->createResponse(401);
            $response = $this->authenticator->challenge($response);
            $response->getBody()->write('Your request was made with invalid credentials.');

            return $response;
        }

        return $handler->handle($request);
    }

    public function setRequestName(string $name): void
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
