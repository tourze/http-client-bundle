<?php

namespace HttpClientBundle\Request;

interface AutoRetryRequest
{
    /**
     * 获取最大重试次数，一般我们传3
     *
     * @see https://symfony.com/blog/new-in-symfony-5-2-retryable-http-client
     */
    public function getMaxRetries(): int;
}
