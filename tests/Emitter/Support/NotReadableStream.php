<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Tests\Emitter\Support;

use Psr\Http\Message\StreamInterface;

class NotReadableStream implements StreamInterface
{
    public function __toString(): string
    {
        throw new \RuntimeException();
    }
    public function close(): void
    {
    }
    public function detach()
    {
        return null;
    }
    public function getSize(): ?int
    {
        return null;
    }
    public function tell(): int
    {
        throw new \RuntimeException();
    }
    public function eof(): bool
    {
        return false;
    }
    public function isSeekable(): bool
    {
        return false;
    }
    public function seek($offset, $whence = SEEK_SET): void
    {
        throw new \RuntimeException();
    }
    public function rewind(): void
    {
        throw new \RuntimeException();
    }
    public function isWritable(): bool
    {
        return false;
    }
    public function write($string): int
    {
        throw new \RuntimeException();
    }
    public function isReadable(): bool
    {
        return false;
    }
    public function read($length): string
    {
        throw new \RuntimeException();
    }
    public function getContents(): string
    {
        throw new \RuntimeException();
    }
    public function getMetadata($key = null)
    {
        return null;
    }
}
