<?php

declare(strict_types=1);

namespace HttpClientBundle\Tests\Client;

use Symfony\Component\Lock\SharedLockInterface;

/**
 * 测试用的 SharedLock 实现
 *
 * 包装真实锁并追踪操作次数。
 */
final class TestSharedLock implements SharedLockInterface
{
    public function __construct(
        private readonly SharedLockInterface $realLock,
        private readonly LockHttpClientTest $testCase,
    ) {
    }

    public function acquire(bool $blocking = false): bool
    {
        ++$this->testCase->lockAcquireCount;

        // 如果测试配置为失败，则返回 false
        if (!$this->testCase->lockShouldSucceed) {
            return false;
        }

        // 否则使用真实锁的行为
        return $this->realLock->acquire($blocking);
    }

    public function acquireRead(bool $blocking = false): bool
    {
        // 委托给 acquire 方法
        return $this->acquire($blocking);
    }

    public function refresh(?float $ttl = null): void
    {
        $this->realLock->refresh($ttl);
    }

    public function isAcquired(): bool
    {
        return $this->realLock->isAcquired();
    }

    public function release(): void
    {
        ++$this->testCase->lockReleaseCount;
        $this->realLock->release();
    }

    public function isExpired(): bool
    {
        return $this->realLock->isExpired();
    }

    public function getRemainingLifetime(): ?float
    {
        return $this->realLock->getRemainingLifetime();
    }
}
