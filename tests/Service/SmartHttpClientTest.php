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
    private SmartHttpClient|MockObject $client;
    private CacheInterface|MockObject $cache;
    private ContextServiceInterface|MockObject $contextService;
    private HttpClientInterface|MockObject $innerClient;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheInterface::class);
        $this->contextService = $this->createMock(ContextServiceInterface::class);
        $this->innerClient = $this->createMock(HttpClientInterface::class);

        $this->client = $this->getMockBuilder(SmartHttpClient::class)
            ->setConstructorArgs([$this->cache, $this->contextService])
            ->onlyMethods(['getInner'])
            ->getMock();

        $this->client->expects($this->any())
            ->method('getInner')
            ->willReturn($this->innerClient);
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

    public function testRequest(): void
    {
        $method = 'GET';
        $url = 'https://example.com';
        $options = [];

        /** @var ResponseInterface&MockObject */
        $response = $this->createMock(ResponseInterface::class);

        $this->innerClient->expects($this->once())
            ->method('request')
            ->with($method, $url, $options)
            ->willReturn($response);

        $result = $this->client->request($method, $url, $options);
        $this->assertSame($response, $result);
    }

    public function testWithOptions(): void
    {
        $options = ['timeout' => 30];

        /** @var HttpClientInterface&MockObject */
        $newInnerClient = $this->createMock(HttpClientInterface::class);

        $this->innerClient->expects($this->once())
            ->method('withOptions')
            ->with($options)
            ->willReturn($newInnerClient);

        $newClient = $this->client->withOptions($options);

        $this->assertNotSame($this->client, $newClient);
        $this->assertSame($newInnerClient, $newClient->getInnerClient());
    }
}
