<?php

namespace HttpClientBundle\Tests\Service;

use HttpClientBundle\Service\SmartHttpClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Tourze\Symfony\RuntimeContextBundle\Service\ContextServiceInterface;

/**
 * @covers \HttpClientBundle\Service\SmartHttpClient
 */
class SmartHttpClientTest extends TestCase
{
    private SmartHttpClient $client;
    private CacheInterface|MockObject $cache;
    private ContextServiceInterface|MockObject $contextService;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheInterface::class);
        $this->contextService = $this->createMock(ContextServiceInterface::class);
        
        // 设置协程支持默认为 false，这样会使用 CurlHttpClient
        $this->contextService->method('supportCoroutine')->willReturn(false);

        $this->client = new SmartHttpClient($this->cache, $this->contextService);
    }

    public function testRefreshDomainResolveCache(): void
    {
        $host = 'example.com';
        $ip = '93.184.216.34';

        $this->cache->expects($this->once())
            ->method('get')
            ->with('api-client-resolve-example.com', $this->isType('callable'))
            ->willReturn($ip);

        $result = $this->client->refreshDomainResolveCache($host);
        $this->assertEquals($ip, $result);
    }

    public function testWithOptions(): void
    {
        $options = ['timeout' => 30];

        $newClient = $this->client->withOptions($options);

        $this->assertNotSame($this->client, $newClient);
        $this->assertInstanceOf(SmartHttpClient::class, $newClient);
    }
}
