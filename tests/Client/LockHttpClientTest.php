<?php

namespace HttpClientBundle\Tests\Client;

use HttpClientBundle\Client\LockHttpClient;
use HttpClientBundle\Exception\LockTimeoutHttpClientException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @covers \HttpClientBundle\Client\LockHttpClient
 */
class LockHttpClientTest extends TestCase
{
    private LockHttpClient $client;
    private HttpClientInterface|MockObject $innerClient;
    private LockFactory|MockObject $lockFactory;

    protected function setUp(): void
    {
        /** @var HttpClientInterface&MockObject $innerClient */
        $this->innerClient = $this->createMock(HttpClientInterface::class);
        /** @var LockFactory&MockObject $lockFactory */
        $this->lockFactory = $this->createMock(LockFactory::class);
        $this->client = new LockHttpClient($this->innerClient, $this->lockFactory);
    }

    public function testRequestWithoutLocking(): void
    {
        $method = 'GET';
        $url = 'https://example.com';
        $options = [];

        $response = $this->createMock(ResponseInterface::class);

        $this->innerClient->expects($this->once())
            ->method('request')
            ->with($method, $url, $options)
            ->willReturn($response);

        $this->lockFactory->expects($this->never())
            ->method('createLock');

        $result = $this->client->request($method, $url, $options);

        $this->assertSame($response, $result);
    }

    public function testRequestWithLockingSuccess(): void
    {
        $method = 'GET';
        $url = 'https://example.com';
        $lockName = 'test-lock';
        $options = [
            'lock_key' => $lockName,
        ];

        $optionsNoLock = $this->logicalNot($this->arrayHasKey('lock_key'));

        $response = $this->createMock(ResponseInterface::class);
        $lock = $this->createMock(LockInterface::class);

        $lock->expects($this->once())
            ->method('acquire')
            ->with(true)
            ->willReturn(true);

        $lock->expects($this->once())
            ->method('release');

        $this->lockFactory->expects($this->once())
            ->method('createLock')
            ->with($lockName)
            ->willReturn($lock);

        $this->innerClient->expects($this->once())
            ->method('request')
            ->with($method, $url, $optionsNoLock)
            ->willReturn($response);

        $result = $this->client->request($method, $url, $options);

        $this->assertSame($response, $result);
    }

    public function testRequestWithLockingFailure(): void
    {
        $method = 'GET';
        $url = 'https://example.com';
        $lockName = 'test-lock';
        $options = [
            'lock_key' => $lockName,
        ];

        $lock = $this->createMock(LockInterface::class);

        $lock->expects($this->once())
            ->method('acquire')
            ->with(true)
            ->willReturn(false);

        $lock->expects($this->never())
            ->method('release');

        $this->lockFactory->expects($this->once())
            ->method('createLock')
            ->with($lockName)
            ->willReturn($lock);

        $this->innerClient->expects($this->never())
            ->method('request');

        $this->expectException(LockTimeoutHttpClientException::class);
        $this->client->request($method, $url, $options);
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
        $this->assertInstanceOf(LockHttpClient::class, $newClient);
    }
}
