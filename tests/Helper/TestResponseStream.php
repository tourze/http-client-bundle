<?php

declare(strict_types=1);

namespace HttpClientBundle\Tests\Helper;

use Symfony\Contracts\HttpClient\ChunkInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

/**
 * 测试用 ResponseStream 实现
 */
class TestResponseStream implements ResponseStreamInterface
{
    public function key(): ResponseInterface
    {
        throw new \LogicException('Test implementation - not implemented');
    }

    public function current(): ChunkInterface
    {
        throw new \LogicException('Test implementation - not implemented');
    }

    public function next(): void
    {
    }

    public function rewind(): void
    {
    }

    public function valid(): bool
    {
        return false;
    }
}
