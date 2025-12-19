<?php

declare(strict_types=1);

namespace HttpClientBundle\Tests\Client;

use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\SharedLockInterface;
use Symfony\Component\Lock\Store\InMemoryStore;

/**
 * 测试用的 LockFactory 实现
 *
 * 提供计数器来追踪锁的获取和释放次数，便于验证测试行为。
 */
final class TestLockFactory extends LockFactory
{
    public function __construct(private readonly LockHttpClientTest $testCase)
    {
        // 使用 InMemoryStore 作为真实的锁存储
        parent::__construct(new InMemoryStore());
    }

    public function createLock(string $resource, ?float $ttl = 300.0, bool $autoRelease = true): SharedLockInterface
    {
        // 创建一个包装了真实锁的计数器锁
        $realLock = parent::createLock($resource, $ttl, $autoRelease);

        return new TestSharedLock($realLock, $this->testCase);
    }
}
