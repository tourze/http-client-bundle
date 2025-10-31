<?php

namespace HttpClientBundle\Tests\Client;

use HttpClientBundle\Client\CacheHttpClient;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(CacheHttpClient::class)]
#[RunTestsInSeparateProcesses]
final class CacheHttpClientTest extends AbstractIntegrationTestCase
{
    private CacheHttpClient $client;

    private HttpClientInterface&MockObject $innerClient;

    private CacheInterface&MockObject $cache;

    protected function onSetUp(): void
    {
        // AbstractIntegrationTestCase 要求的抽象方法实现
        // 由于我们不使用标准的 setUp 流程，这里留空
    }

    private function createCacheHttpClient(): void
    {
        /** @var HttpClientInterface&MockObject $innerClient */
        $innerClient = $this->createMock(HttpClientInterface::class);
        /** @var CacheInterface&MockObject $cache */
        $cache = $this->createMock(CacheInterface::class);

        $this->innerClient = $innerClient;
        $this->cache = $cache;

        // @phpstan-ignore-next-line integrationTest.noDirectInstantiationOfCoveredClass - 需要使用Mock依赖验证行为
        $this->client = new CacheHttpClient($this->innerClient, $this->cache);
    }

    public function testRequestWithoutCaching(): void
    {
        $this->createCacheHttpClient();

        $method = 'GET';
        $url = 'https://example.com';
        /** @var array<string, mixed> */
        $options = [];

        $response = $this->createMock(ResponseInterface::class);

        $this->innerClient->expects($this->once())
            ->method('request')
            ->with($method, $url, $options)
            ->willReturn($response)
        ;

        $result = $this->client->request($method, $url, $options);

        $this->assertSame($response, $result);
    }

    public function testRequestWithCaching(): void
    {
        $this->createCacheHttpClient();

        $method = 'GET';
        $url = 'https://example.com';
        $options = [
            'cache_key' => 'test-cache-key',
            'cache_ttl' => 3600,
        ];

        $response = $this->createMock(ResponseInterface::class);

        // 缓存功能被临时禁用，所以不会调用 cache->get()
        // 而是直接调用内部 client，并且 cache_key 会被移除
        $this->innerClient->expects($this->once())
            ->method('request')
            ->with($method, $url, self::logicalNot(self::arrayHasKey('cache_key')))
            ->willReturn($response)
        ;

        $this->cache->expects($this->never())
            ->method('get')
        ;

        $result = $this->client->request($method, $url, $options);

        $this->assertSame($response, $result);
    }

    public function testWithOptions(): void
    {
        $this->createCacheHttpClient();

        /** @var array<string, mixed> */
        $options = ['timeout' => 30];
        $newInnerClient = $this->createMock(HttpClientInterface::class);

        $this->innerClient->expects($this->once())
            ->method('withOptions')
            ->with($options)
            ->willReturn($newInnerClient)
        ;

        $newClient = $this->client->withOptions($options);

        $this->assertNotSame($this->client, $newClient);
        $this->assertNotNull($newClient);
    }

    public function testStream(): void
    {
        $this->createCacheHttpClient();
        $responses = [
            $this->createMock(ResponseInterface::class),
            $this->createMock(ResponseInterface::class),
        ];
        $timeout = 10.0;

        $expectedStream = $this->createMock(ResponseStreamInterface::class);

        $this->innerClient->expects($this->once())
            ->method('stream')
            ->with($responses, $timeout)
            ->willReturn($expectedStream)
        ;

        $result = $this->client->stream($responses, $timeout);

        $this->assertSame($expectedStream, $result);
    }
}
