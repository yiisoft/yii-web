<?php

namespace Yiisoft\Yii\Web\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Yii\Web\Exception\BadUriPrefixException;

/**
 * This middleware supports routing when webroot is not the same folder as public
 */
final class SubFolderMiddleware implements MiddlewareInterface
{
    public ?string $prefix = null;
    protected UrlGeneratorInterface $uriGenerator;
    private Aliases $aliases;

    public function __construct(UrlGeneratorInterface $uriGenerator, Aliases $aliases)
    {
        $this->uriGenerator = $uriGenerator;
        $this->aliases = $aliases;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $uri = $request->getUri();
        $path = $uri->getPath();

        if ($this->prefix === null) {
            // automatically check that the project is in a subfolder
            // and uri contain a prefix
            $scriptName = $request->getServerParams()['SCRIPT_NAME'];
            if (strpos($scriptName, '/', 1) !== false) {
                $length = strrpos($scriptName, '/');
                $prefix = substr($scriptName, 0, $length);
                if (strpos($path, $prefix) === 0) {
                    $this->prefix = $prefix;
                    $this->uriGenerator->setUriPrefix($prefix);
                    $request = $request->withUri($uri->withPath(substr($path, $length)));
                }
            }
        } elseif ($this->prefix !== '') {
            if ($this->prefix[-1] === '/') {
                throw new BadUriPrefixException('Wrong URI prefix value');
            }
            $length = strlen($this->prefix);
            if (strpos($path, $this->prefix) !== 0) {
                throw new BadUriPrefixException('URI prefix does not match');
            }
            $this->uriGenerator->setUriPrefix($this->prefix);
            $request = $request->withUri($uri->withPath(substr($path, $length)));
        }
        // rewrite alias
        $this->aliases->set('@web', $this->prefix . '/');

        return $handler->handle($request);
    }
}
