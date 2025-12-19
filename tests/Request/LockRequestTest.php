<?php

namespace HttpClientBundle\Tests\Request;

use HttpClientBundle\Request\LockRequest;
use HttpClientBundle\Request\RequestInterface;
use HttpClientBundle\Test\RequestTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * @internal
 */
#[CoversClass(LockRequest::class)]
final class LockRequestTest extends RequestTestCase
{
    public function testLockRequestImplementation(): void
    {
        $lockKey = 'test-lock-key';

        $request = new class($lockKey) implements LockRequest, RequestInterface {
            public function __construct(
                private readonly string $key,
            ) {
            }

            public function getRequestPath(): string
            {
                return '/api/test';
            }
            public function getRequestOptions(): array
            {
                return [];
            }

            public function getRequestMethod(): string
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
