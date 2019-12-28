<?php declare(strict_types=1);

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
final class SubFolder implements MiddlewareInterface
{
    public ?string $prefix = null;
    private UrlGeneratorInterface $uriGenerator;
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
        $auto = $this->prefix === null;
        $length = $auto ? 0 : strlen($this->prefix);

        if ($auto) {
            // automatically check that the project is in a subfolder
            // and uri contain a prefix
            $scriptName = $request->getServerParams()['SCRIPT_NAME'];
            if (strpos($scriptName, '/', 1) !== false) {
                $prefix = substr($scriptName, 0, strrpos($scriptName, '/'));
                if (strpos($path, $prefix) === 0) {
                    $this->prefix = $prefix;
                    $length = strlen($this->prefix);
                }
            }
        } elseif ($length > 0) {
            if ($this->prefix[-1] === '/') {
                throw new BadUriPrefixException('Wrong URI prefix value');
            }
            if (strpos($path, $this->prefix) !== 0) {
                throw new BadUriPrefixException('URI prefix does not match');
            }
        }

        if ($length > 0) {
            $newPath = substr($path, $length);
            if ($newPath === '') {
                $newPath = '/';
            }
            if ($newPath[0] !== '/') {
                if (!$auto) {
                    throw new BadUriPrefixException('URI prefix does not match completely');
                }
            } else {
                $request = $request->withUri($uri->withPath($newPath));
                $this->uriGenerator->setUriPrefix($this->prefix);
                // rewrite alias
                $this->aliases->set('@web', $this->prefix . '/');
            }
        }

        return $handler->handle($request);
    }
}
