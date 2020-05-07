<?php

namespace Yiisoft\Yii\Web\ErrorHandler;

use Psr\Http\Message\ServerRequestInterface;

/**
 * ThrowableRendererInterface converts throwable into its string representation
 */
interface ThrowableRendererInterface
{
    /**
     * Convert throwable into its string representation suitable for displaying in production environment
     *
     * @param \Throwable $t
     * @param string $template
     * @param string|null $customPath
     * @return string
     */
    public function render(\Throwable $t, string $template = 'error', string $customPath = null): string;

    /**
     * Convert throwable into its string representation suitable for displaying in development environment
     *
     * @param \Throwable $t
     * @param string $template
     * @param string|null $customPath
     * @return string
     */
    public function renderVerbose(\Throwable $t, string $template = 'exception', string $customPath = null): string;

    public function setRequest(ServerRequestInterface $request): void;
}
