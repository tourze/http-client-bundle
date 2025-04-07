<?php

namespace HttpClientBundle\Request;

/**
 * 带分布式缓存的请求
 */
interface CacheRequest
{
    /**
     * 获取缓存key
     */
    public function getCacheKey(): string;

    /**
     * 缓存时间
     */
    public function getCacheDuration(): int;
}
