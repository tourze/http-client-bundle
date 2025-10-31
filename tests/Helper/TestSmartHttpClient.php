<?php

declare(strict_types=1);

namespace HttpClientBundle\Tests\Helper;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

/**
 * 测试用的HttpClient实现
 */
class TestSmartHttpClient implements HttpClientInterface
{
    public function __construct()
    {
        // 测试用构造函数，无需依赖
    }

    /** @param array<mixed> $options */
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        return new TestResponseInterface();
    }

    public function stream(iterable|ResponseInterface $responses, ?float $timeout = null): ResponseStreamInterface
    {
        return TestEntityGenerator::createResponseStream();
    }

    /** @param array<mixed> $options */
    public function withOptions(array $options): static
    {
        return $this;
    }
}
