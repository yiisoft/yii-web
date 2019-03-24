<?php


namespace yii\web\router;

use Psr\Http\Message\ServerRequestInterface;

class NoMatch extends \Exception
{
    /**
     * @var ServerRequestInterface
     */
    private $request;

    public function __construct(ServerRequestInterface $request)
    {
        $this->request = $request;
        parent::__construct('No route matching ' . $request->getMethod() . ' ' . $request->getUri() . ' was found.');
    }

    /**
     * @return ServerRequestInterface
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }
}
