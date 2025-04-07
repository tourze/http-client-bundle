<?php

namespace HttpClientBundle\Client;

use HttpClientBundle\Request\RequestInterface;

trait ClientTrait
{
    /**
     * 优先使用Request中定义的地址
     */
    protected function getRequestUrl(RequestInterface $request): string
    {
        $path = ltrim($request->getRequestPath(), '/');
        if (str_starts_with($path, 'https://')) {
            return $path;
        }
        if (str_starts_with($path, 'http://')) {
            return $path;
        }

        $domain = trim($this->getBaseUrl());
        if (empty($domain)) {
            throw new \RuntimeException(static::class . '缺少getBaseUrl的定义');
        }

        return "{$domain}/{$path}";
    }
}
