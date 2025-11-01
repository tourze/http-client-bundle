<?php

namespace HttpClientBundle\Tests\Request;

use HttpClientBundle\Request\AutoRetryRequest;
use HttpClientBundle\Request\RequestInterface;
use HttpClientBundle\Test\RequestTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * @internal
 */
#[CoversClass(AutoRetryRequest::class)]
final class AutoRetryRequestTest extends RequestTestCase
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

            /** @phpstan-ignore-next-line method.childReturnType */
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
