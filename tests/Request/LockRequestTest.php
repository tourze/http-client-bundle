<?php

namespace HttpClientBundle\Tests\Request;

use HttpClientBundle\Request\LockRequest;
use HttpClientBundle\Request\RequestInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \HttpClientBundle\Request\LockRequest
 */
class LockRequestTest extends TestCase
{
    public function testLockRequestImplementation(): void
    {
        $lockKey = 'test-lock-key';

        $request = new class($lockKey) implements LockRequest, RequestInterface {
            public function __construct(
                private readonly string $key
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

            public function getLockKey(): string
            {
                return $this->key;
            }
        };

        $this->assertEquals($lockKey, $request->getLockKey());
    }
}
