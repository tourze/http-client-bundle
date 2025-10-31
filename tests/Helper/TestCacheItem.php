<?php

declare(strict_types=1);

namespace HttpClientBundle\Tests\Helper;

use Symfony\Contracts\Cache\ItemInterface;

/**
 * 测试用 CacheItem 实现
 */
class TestCacheItem implements ItemInterface
{
    private mixed $value = null;

    private bool $hit = false;

    public function getKey(): string
    {
        return 'test-key';
    }

    public function get(): mixed
    {
        return $this->value;
    }

    public function isHit(): bool
    {
        return $this->hit;
    }

    public function set(mixed $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function expiresAt(?\DateTimeInterface $expiration): static
    {
        return $this;
    }

    public function expiresAfter(\DateInterval|int|null $time): static
    {
        return $this;
    }

    public function tag(iterable|string $tags): static
    {
        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return [];
    }
}
