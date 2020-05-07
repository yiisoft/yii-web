<?php


namespace Yiisoft\Yii\Web\Tests\Middleware\Mock;

use Yiisoft\Yii\Web\ErrorHandler\ThrowableRenderer;

class MockThrowableRenderer extends ThrowableRenderer
{
    /**
     * @var string
     */
    private $response;

    public function __construct(string $response)
    {
        $this->response = $response;
    }

    public function render(\Throwable $t, string $template = 'error', string $customPath = null): string
    {
        return $this->response;
    }

    public function renderVerbose(\Throwable $t, string $template = 'exception', string $customPath = null): string
    {
        return $this->response;
    }
}
