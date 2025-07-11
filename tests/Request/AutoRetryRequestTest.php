<?php

namespace HttpClientBundle\Tests\Request;

use HttpClientBundle\Request\AutoRetryRequest;
use HttpClientBundle\Request\RequestInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \HttpClientBundle\Request\AutoRetryRequest
 */
class AutoRetryRequestTest extends TestCase
{
    public function testGetMaxRetries(): void
    {
        $maxRetries = 3;
        $request = new class($maxRetries) implements AutoRetryRequest, RequestInterface {
            public function __construct(private readonly int $retries)
            {
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

            public function getMaxRetries(): int
            {
                return $this->retries;
            }
        };

        $this->assertEquals($maxRetries, $request->getMaxRetries());
    }
}
