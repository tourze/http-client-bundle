<?php

namespace HttpClientBundle\Response;

use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * 缓存后的结果
 */
readonly class CacheResponse implements ResponseInterface
{
    /**
     * @param array<string, list<string>> $headers
     */
    public function __construct(
        private int $statusCode,
        private array $headers,
        private string $content,
        private mixed $info,
    ) {
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

    /**
     * @return array<string, mixed>
     */
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
