<?php
namespace Yiisoft\Yii\Web\Auth;

use Psr\Http\Message\ServerRequestInterface;

trait HttpHeaderAuthTrait
{
    /**
     * @var string the HTTP header name
     */
    private $header = 'X-Api-Key';
    /**
     * @var string a pattern to use to extract the HTTP authentication value
     */
    private $pattern;

    private function getAuthToken(ServerRequestInterface $request): ?string
    {
        $authHeaders = $request->getHeader($this->header);
        $authHeader = \reset($authHeaders);
        if ($authHeader !== null) {
            if ($this->pattern !== null) {
                if (preg_match($this->pattern, $authHeader, $matches)) {
                    $authHeader = $matches[1];
                } else {
                    return null;
                }
            }
            return $authHeader;
        }
        return null;
    }
}