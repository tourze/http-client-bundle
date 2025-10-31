<?php

namespace HttpClientBundle\Tests\Request;

use HttpClientBundle\Request\ApiRequest;

class ConcreteApiRequest extends ApiRequest
{
    private string $path;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $options;

    private ?string $method;

    /**
     * @param array<string, mixed>|null $options
     */
    public function __construct(string $path, ?array $options = null, ?string $method = null)
    {
        $this->path = $path;
        $this->options = $options;
        $this->method = $method;
    }

    public function getRequestPath(): string
    {
        return $this->path;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getRequestOptions(): ?array
    {
        return $this->options;
    }

    public function getRequestMethod(): ?string
    {
        return $this->method ?? parent::getRequestMethod();
    }
}
