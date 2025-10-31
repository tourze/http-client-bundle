<?php

namespace HttpClientBundle\Tests\Client;

use Symfony\Component\Lock\SharedLockInterface;

/**
 * 带计数器的测试用 SharedLock 实现
 */
class TestSharedLockWithCounters implements SharedLockInterface
{
    private bool $acquired = false;

    public function __construct(private LockHttpClientTest $testCase)
    {
    }

    public function acquire(bool $blocking = false): bool
    {
        ++$this->testCase->lockAcquireCallCount;
        $this->acquired = $this->testCase->lockShouldSucceed;

        return $this->acquired;
    }

    public function acquireRead(bool $blocking = false): bool
    {
        return $this->acquire($blocking);
    }

    public function refresh(?float $ttl = null): void
    {
        // 简化的刷新实现
    }

    public function isAcquired(): bool
    {
        return $this->acquired;
    }

    public function release(): void
    {
        ++$this->testCase->lockReleaseCallCount;
        $this->acquired = false;
    }

    public function isExpired(): bool
    {
        return false;
    }

    public function getRemainingLifetime(): ?float
    {
        return null;
    }
}
