<?php

namespace HttpClientBundle\Request;

interface RequestInterface
{
    /**
     * 接口路径
     */
    public function getRequestPath(): string;

    /**
     * 接口发送数据
     * @return array<array-key, mixed>|null
     */
    public function getRequestOptions(): ?array;

    /**
     * 请求级别覆盖默认方法
     */
    public function getRequestMethod(): ?string;
}
