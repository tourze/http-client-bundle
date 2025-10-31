<?php

declare(strict_types=1);

namespace HttpClientBundle\Tests\Helper;

use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\SharedLockInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\ChunkInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

/**
 * 测试实体生成器 - 用于创建复杂的测试对象
 */
class TestEntityGenerator
{
    public static function createSharedLock(): SharedLockInterface
    {
        return new TestSharedLock();
    }

    public static function createCacheItem(): ItemInterface
    {
        return new TestCacheItem();
    }

    public static function createResponseStream(): ResponseStreamInterface
    {
        return new TestResponseStream();
    }

    public static function createLogger(): LoggerInterface
    {
        return new TestLogger();
    }
}
