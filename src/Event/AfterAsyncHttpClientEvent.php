<?php

namespace HttpClientBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * 异步发送请求的后置事件
 */
class AfterAsyncHttpClientEvent extends Event
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

    private array $params = [];

    public function getParams(): array
    {
        return $this->params;
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }
}
