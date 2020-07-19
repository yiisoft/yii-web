<?php

declare(strict_types=1);

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
     * @return string
     */
    public function render(\Throwable $t): string;

    /**
     * Convert throwable into its string representation suitable for displaying in development environment
     *
     * @param \Throwable $t
     * @return string
     */
    public function renderVerbose(\Throwable $t): string;

    public function setRequest(ServerRequestInterface $request): void;
}
