<?php

declare(strict_types=1);

namespace HttpClientBundle\Tests\Helper;

use Symfony\Component\Lock\SharedLockInterface;

/**
 * 测试用 SharedLock 实现
 */
class TestSharedLock implements SharedLockInterface
{
    private bool $acquired = false;

    public function acquire(bool $blocking = false): bool
    {
        $this->acquired = true;

        return true;
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
        $this->acquired = false;
    }

    public function isExpired(): bool
    {
        return false;
    }

    public function getRemainingLifetime(): ?float
    {
        return 300.0;
    }
}
