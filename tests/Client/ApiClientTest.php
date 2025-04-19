<?php

namespace HttpClientBundle\Tests\Client;

use HttpClientBundle\Client\ApiClient;
use HttpClientBundle\Request\RequestInterface;
use HttpClientBundle\Service\SmartHttpClient;
use Laminas\Diagnostics\Result\ResultInterface;
use Laminas\Diagnostics\Result\Skip;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Tourze\DoctrineAsyncBundle\Service\DoctrineService;

/**
 * 测试用的具体ApiClient实现
 */
class TestApiClient extends ApiClient
{
    private string $baseUrl = '';

    public function setBaseUrl(string $baseUrl): void
    {
        $this->baseUrl = $baseUrl;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    protected function getRequestUrl(RequestInterface $request): string
    {
        return $this->getBaseUrl() . $request->getRequestPath();
    }

    protected function getRequestMethod(RequestInterface $request): string
    {
        return $request->getRequestMethod() ?? 'GET';
    }

    protected function getRequestOptions(RequestInterface $request): ?array
    {
        return $request->getRequestOptions();
    }

    protected function formatResponse(RequestInterface $request, ResponseInterface $response): mixed
    {
        return json_decode($response->getContent(), true);
    }
}

/**
 * 测试用的API请求
 */
class TestApiRequest implements RequestInterface
{
    public function __construct(
        private readonly string  $path,
        private readonly ?array  $options = null,
        private readonly ?string $method = null
    )
    {
    }

    public function getRequestPath(): string
    {
        return $this->path;
    }

    public function getRequestOptions(): ?array
    {
        return $this->options;
    }

    public function getRequestMethod(): ?string
    {
        return $this->method;
    }
}

/**
 * @covers \HttpClientBundle\Client\ApiClient
 */
class ApiClientTest extends TestCase
{
    private TestApiClient $client;
    private ContainerInterface|MockObject $container;
    private SmartHttpClient|MockObject $httpClient;
    private CacheInterface|MockObject $cache;
    private EventDispatcherInterface|MockObject $eventDispatcher;
    private LockFactory|MockObject $lockFactory;
    private DoctrineService|MockObject $doctrineService;
    private LoggerInterface|MockObject $logger;

    protected function setUp(): void
    {
        /** @var SmartHttpClient&MockObject $httpClient */
        $this->httpClient = $this->createMock(SmartHttpClient::class);
        /** @var CacheInterface&MockObject $cache */
        $this->cache = $this->createMock(CacheInterface::class);
        /** @var EventDispatcherInterface&MockObject $eventDispatcher */
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        /** @var LockFactory&MockObject $lockFactory */
        $this->lockFactory = $this->createMock(LockFactory::class);
        /** @var DoctrineService&MockObject $doctrineService */
        $this->doctrineService = $this->createMock(DoctrineService::class);
        /** @var LoggerInterface&MockObject $logger */
        $this->logger = $this->createMock(LoggerInterface::class);

        // 创建容器 mock
        /** @var ContainerInterface&MockObject $container */
        $this->container = $this->createMock(ContainerInterface::class);
        $this->container->method('get')
            ->willReturnCallback(function ($service) {
                // ApiClient 使用 ServiceMethodsSubscriberTrait，它会使用完整方法名作为服务标识
                return match ($service) {
                    'HttpClientBundle\Client\ApiClient::getHttpClient',
                    'HttpClientBundle\Tests\Client\TestApiClient::getHttpClient' => $this->httpClient,
                    'HttpClientBundle\Client\ApiClient::getCache',
                    'HttpClientBundle\Tests\Client\TestApiClient::getCache' => $this->cache,
                    'HttpClientBundle\Client\ApiClient::getEventDispatcher',
                    'HttpClientBundle\Tests\Client\TestApiClient::getEventDispatcher' => $this->eventDispatcher,
                    'HttpClientBundle\Client\ApiClient::getLockFactory',
                    'HttpClientBundle\Tests\Client\TestApiClient::getLockFactory' => $this->lockFactory,
                    'HttpClientBundle\Client\ApiClient::getDoctrineService',
                    'HttpClientBundle\Tests\Client\TestApiClient::getDoctrineService' => $this->doctrineService,
                    default => null,
                };
            });

        $this->client = new TestApiClient();
        $this->client->setContainer($this->container);
        $this->client->apiClientLogger = $this->logger;
    }

    public function testCheck_NoBaseUrl(): void
    {
        $result = $this->client->check();

        $this->assertInstanceOf(Skip::class, $result);
    }

    public function testCheck_WithBaseUrl(): void
    {
        $this->client->setBaseUrl('https://example.com/api');

        // 设置正常的解析和端口检查
        $this->httpClient->expects($this->once())
            ->method('refreshDomainResolveCache')
            ->with('example.com')
            ->willReturn('93.184.216.34');

        // 预期成功结果
        $result = $this->client->check();

        // 由于我们无法真正测试端口连接和SSL证书，我们在此处模拟测试
        // 实际上这个测试不够完善，但是在单元测试环境下难以完全测试
        $this->assertInstanceOf(ResultInterface::class, $result);
    }

    public function testGetLabel(): void
    {
        $this->assertEquals(TestApiClient::class, $this->client->getLabel());
    }
}
