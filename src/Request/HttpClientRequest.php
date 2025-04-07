<?php

namespace HttpClientBundle\Request;

/**
 * 没封装，直接转发到HTTP Client的请求类
 */
class HttpClientRequest implements RequestInterface
{
    private string $requestPath;

    public function getRequestPath(): string
    {
        return $this->requestPath;
    }

    public function setRequestPath(string $requestPath): void
    {
        $this->requestPath = $requestPath;
    }

    private ?array $requestOptions = null;

    public function getRequestOptions(): ?array
    {
        return $this->requestOptions;
    }

    public function setRequestOptions(?array $requestOptions): void
    {
        $this->requestOptions = $requestOptions;
    }

    private ?string $requestMethod = null;

    public function getRequestMethod(): ?string
    {
        return $this->requestMethod;
    }

    public function setRequestMethod(?string $requestMethod): void
    {
        $this->requestMethod = $requestMethod;
    }
}
