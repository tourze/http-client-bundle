<?php

declare(strict_types=1);

namespace HttpClientBundle\Tests\Helper;

use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * 测试用的ResponseInterface实现
 */
class TestResponseInterface implements ResponseInterface
{
    public function getStatusCode(): int
    {
        return 200;
    }

    public function getHeaders(bool $throw = true): array
    {
        return [];
    }

    public function getContent(bool $throw = true): string
    {
        return '';
    }

    /**
     * @return array<mixed>
     */
    public function toArray(bool $throw = true): array
    {
        return [];
    }

    public function cancel(): void
    {
    }

    public function getInfo(?string $type = null): mixed
    {
        return null;
    }
}
