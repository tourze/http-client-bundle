<?php

namespace HttpClientBundle\Request;

use Tourze\BacktraceHelper\LogDataInterface;
use Yiisoft\Json\Json;

/**
 * 代表请求
 */
abstract class ApiRequest implements \Stringable, RequestInterface, LogDataInterface
{
    /**
     * 接口路径
     */
    abstract public function getRequestPath(): string;

    /**
     * 接口发送数据
     *
     * @see https://symfony.com/doc/current/http_client.html
     * @return array<array-key, mixed>|null
     */
    abstract public function getRequestOptions(): ?array;

    /**
     * 请求级别覆盖默认方法
     */
    public function getRequestMethod(): ?string
    {
        return null;
    }

    /**
     * 转换为字符串，方便我们打印调试
     *
     * @throws \JsonException
     */
    public function __toString(): string
    {
        return Json::encode($this->generateLogData());
    }

    /**
     * @return array<string, mixed>|null
     */
    public function generateLogData(): ?array
    {
        return [
            '_className' => get_class($this),
            'path' => $this->getRequestPath(),
            'method' => $this->getRequestMethod(),
            'payload' => $this->getRequestOptions(),
        ];
    }
}
