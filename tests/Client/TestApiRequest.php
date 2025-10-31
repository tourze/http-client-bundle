<?php

namespace HttpClientBundle\Tests\Client;

use HttpClientBundle\Request\RequestInterface;

class TestApiRequest implements RequestInterface
{
    public function __construct(
        private readonly string $path,
        /** @var array<string,mixed>|null */
        private readonly ?array $options = null,
        private readonly ?string $method = null,
    ) {
    }

    public function getRequestPath(): string
    {
        return $this->path;
    }

    public function getRequestOptions(): ?array
    {
        return $this->options;
    }

    public function getRequestMethod(): ?string
    {
        return $this->method;
    }
}
