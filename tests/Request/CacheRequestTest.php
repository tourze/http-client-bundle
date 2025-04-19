<?php

namespace HttpClientBundle\Tests\Request;

use HttpClientBundle\Request\CacheRequest;
use HttpClientBundle\Request\RequestInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \HttpClientBundle\Request\CacheRequest
 */
class CacheRequestTest extends TestCase
{
    public function testCacheRequestImplementation(): void
    {
        $cacheKey = 'test-cache-key';
        $cacheDuration = 3600;

        $request = new class($cacheKey, $cacheDuration) implements CacheRequest, RequestInterface {
            public function __construct(
                private readonly string $key,
                private readonly int    $duration
            )
            {
            }

            public function getRequestPath(): string
            {
                return '/api/test';
            }

            public function getRequestOptions(): ?array
            {
                return [];
            }

            public function getRequestMethod(): ?string
            {
                return 'GET';
            }

            public function getCacheKey(): string
            {
                return $this->key;
            }

            public function getCacheDuration(): int
            {
                return $this->duration;
            }
        };

        $this->assertEquals($cacheKey, $request->getCacheKey());
        $this->assertEquals($cacheDuration, $request->getCacheDuration());
    }
}
