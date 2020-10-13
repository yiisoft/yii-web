<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web;

interface ApplicationParametersInterface
{
    public function get(string $name): string;

    public function getAdminEmail(): string;

    public function getCharset(): string;

    public function getEmail(): string;

    public function getInfoEmail(): string;

    public function getLanguage(): string;

    public function getName(): string;

    public function getSupportEmail(): string;

    public function has(string $name): bool;
}
