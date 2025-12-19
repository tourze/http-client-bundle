<?php

namespace HttpClientBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * 异步发送请求的后置事件
 */
final class AfterAsyncHttpClientEvent extends Event
{
    private string $result = '';

    public function getResult(): string
    {
        return $this->result;
    }

    public function setResult(string $result): void
    {
        $this->result = $result;
    }

    /**
     * @var array<string, mixed>
     */
    private array $params = [];

    /**
     * @return array<string, mixed>
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @param array<string, mixed> $params
     */
    public function setParams(array $params): void
    {
        $this->params = $params;
    }
}
