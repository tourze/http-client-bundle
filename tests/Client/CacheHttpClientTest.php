<?php

namespace HttpClientBundle\Tests\Client;

use HttpClientBundle\Client\CacheHttpClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @covers \HttpClientBundle\Client\CacheHttpClient
 */
class CacheHttpClientTest extends TestCase
{
    private CacheHttpClient $client;
    private HttpClientInterface|MockObject $innerClient;
    private CacheInterface|MockObject $cache;

    protected function setUp(): void
    {
        /** @var HttpClientInterface&MockObject $innerClient */
        $this->innerClient = $this->createMock(HttpClientInterface::class);
        /** @var CacheInterface&MockObject $cache */
        $this->cache = $this->createMock(CacheInterface::class);
        $this->client = new CacheHttpClient($this->innerClient, $this->cache);
    }

    public function testRequestWithoutCaching(): void
    {
        $method = 'GET';
        $url = 'https://example.com';
        $options = [];

        $response = $this->createMock(ResponseInterface::class);

        $this->innerClient->expects($this->once())
            ->method('request')
            ->with($method, $url, $options)
            ->willReturn($response);

        $result = $this->client->request($method, $url, $options);

        $this->assertSame($response, $result);
    }

    public function testRequestWithCaching(): void
    {
        $method = 'GET';
        $url = 'https://example.com';
        $options = [
            'cache_key' => 'test-cache-key',
            'cache_ttl' => 3600,
        ];

        $response = $this->createMock(ResponseInterface::class);
        $responseContent = '{"test": "data"}';
        $responseStatusCode = 200;
        $responseHeaders = ['Content-Type' => ['application/json']];
        $responseInfo = ['url' => $url];

        $response->expects($this->once())
            ->method('getContent')
            ->willReturn($responseContent);

        $response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn($responseStatusCode);

        $response->expects($this->once())
            ->method('getHeaders')
            ->willReturn($responseHeaders);

        $response->expects($this->once())
            ->method('getInfo')
            ->with('debug')
            ->willReturn($responseInfo);

        $this->innerClient->expects($this->once())
            ->method('request')
            ->with($method, $url, $this->logicalNot($this->arrayHasKey('cache_key')))
            ->willReturn($response);

        $this->cache->expects($this->once())
            ->method('get')
            ->with('test-cache-key', $this->isType('callable'))
            ->willReturnCallback(function ($key, $callback) {
                $item = $this->createMock(ItemInterface::class);
                $item->expects($this->once())
                    ->method('expiresAfter')
                    ->with(3600);
                $item->expects($this->once())
                    ->method('set')
                    ->with($this->isInstanceOf(ResponseInterface::class));
                return $callback($item);
            });

        $result = $this->client->request($method, $url, $options);

        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testWithOptions(): void
    {
        $options = ['timeout' => 30];
        $newInnerClient = $this->createMock(HttpClientInterface::class);

        $this->innerClient->expects($this->once())
            ->method('withOptions')
            ->with($options)
            ->willReturn($newInnerClient);

        $newClient = $this->client->withOptions($options);

        $this->assertNotSame($this->client, $newClient);
        $this->assertInstanceOf(CacheHttpClient::class, $newClient);
    }
}
