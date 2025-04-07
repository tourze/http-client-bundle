<?php

namespace HttpClientBundle\Response;

use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * 缓存后的结果
 */
#[When(env: 'never')]
class CacheResponse implements ResponseInterface
{
    public function __construct(
        private readonly int $statusCode,
        private readonly array $headers,
        private readonly string $content,
        private readonly mixed $info,
    )
    {
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(bool $throw = true): array
    {
        return $this->headers;
    }

    public function getContent(bool $throw = true): string
    {
        return $this->content;
    }

    public function cancel(): void
    {
        // 缓存就没得cancel啦
    }

    public function getInfo(?string $type = null): mixed
    {
        return $this->info;
    }

    protected function close(): void
    {
        // 没得close
    }

    public function toArray(bool $throw = true): array
    {
        return [
            'status_code' => $this->statusCode,
            'headers' => $this->headers,
            'content' => $this->content,
            'info' => $this->info,
        ];
    }
}
