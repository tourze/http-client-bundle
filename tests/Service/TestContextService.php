<?php

namespace HttpClientBundle\Tests\Service;

use Tourze\Symfony\RuntimeContextBundle\Service\ContextServiceInterface;

/**
 * 测试用 ContextService 实现
 */
class TestContextService implements ContextServiceInterface
{
    private bool $supportCoroutine = false;

    public function supportCoroutine(): bool
    {
        return $this->supportCoroutine;
    }

    public function setSupportCoroutine(bool $support): void
    {
        $this->supportCoroutine = $support;
    }

    public function getId(): string
    {
        return 'test-context';
    }

    public function defer(callable $callback): void
    {
        $callback();
    }

    public function reset(): void
    {
        $this->supportCoroutine = false;
    }
}
