<?php

namespace HttpClientBundle\Event;

trait RequestTrait
{
    private string $url;

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    private string $method;

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setMethod(string $method): void
    {
        $this->method = $method;
    }

    /**
     * @var array<array-key, mixed>
     */
    private array $options;

    /**
     * @return array<array-key, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array<array-key, mixed> $options
     */
    public function setOptions(array $options): void
    {
        $this->options = $options;
    }
}
