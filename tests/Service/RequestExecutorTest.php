<?php

declare(strict_types=1);

namespace HttpClientBundle\Tests\Service;

use HttpClientBundle\Service\ProxyManager;
use HttpClientBundle\Service\RequestExecutor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 */
#[CoversClass(RequestExecutor::class)]
class RequestExecutorTest extends TestCase
{
    private RequestExecutor $requestExecutor;

    private EventDispatcherInterface&MockObject $eventDispatcher;

    private ProxyManager&MockObject $proxyManager;

    protected function setUp(): void
    {
        /** @var EventDispatcherInterface&MockObject $eventDispatcher */
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        /** @var ProxyManager&MockObject $proxyManager */
        $proxyManager = $this->createMock(ProxyManager::class);

        $this->eventDispatcher = $eventDispatcher;
        $this->proxyManager = $proxyManager;
        $this->requestExecutor = new RequestExecutor($this->eventDispatcher, $this->proxyManager);
    }

    public function testSendRequest(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $response->method('getInfo')->willReturn(200);

        $this->proxyManager
            ->method('applyProxySettings')
            ->willReturnArgument(1)
        ;

        $httpClient
            ->method('request')
            ->willReturn($response)
        ;

        $result = $this->requestExecutor->sendRequest($httpClient, 'GET', 'https://example.com', []);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('response', $result);
        $this->assertArrayHasKey('duration', $result);
        $this->assertSame($response, $result['response']);
        $this->assertIsFloat($result['duration']);
    }
}
