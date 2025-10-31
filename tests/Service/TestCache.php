<?php

namespace HttpClientBundle\Tests\Service;

use HttpClientBundle\Tests\Helper\TestEntityGenerator;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * 测试用 Cache 实现
 */
class TestCache implements CacheInterface
{
    /** @var array<string, mixed> */
    private array $data = [];

    /** @var array<string, array<int, string>> */
    private array $callLog = [];

    /** @param ?array<mixed> &$metadata */
    public function get(string $key, callable $callback, ?float $beta = null, ?array &$metadata = null): mixed
    {
        $this->callLog['get'][] = $key;
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }

        $item = TestEntityGenerator::createCacheItem();

        return $callback($item, false);
    }

    public function delete(string $key): bool
    {
        unset($this->data[$key]);

        return true;
    }

    /**
     * @return array<mixed>
     */
    public function getCallLog(): array
    {
        return $this->callLog;
    }

    public function setTestData(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }
}
