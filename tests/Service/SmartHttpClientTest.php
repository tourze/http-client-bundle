<?php

namespace HttpClientBundle\Tests\Service;

use HttpClientBundle\Service\SmartHttpClient;
use HttpClientBundle\Tests\Helper\TestEntityGenerator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\ChunkInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\Symfony\RuntimeContextBundle\Service\ContextServiceInterface;

/**
 * @internal
 */
#[CoversClass(SmartHttpClient::class)]
#[RunTestsInSeparateProcesses]
final class SmartHttpClientTest extends AbstractIntegrationTestCase
{
    private SmartHttpClient $client;

    private CacheInterface $cache;

    private ContextServiceInterface $contextService;

    private LoggerInterface $logger;

    protected function onSetUp(): void
    {
        // AbstractIntegrationTestCase 要求的抽象方法实现
        // 由于我们不使用标准的 setUp 流程，这里留空
    }

    /**
     * 创建简化的 CacheInterface 测试实现
     */
    private function createTestCacheImplementation(): CacheInterface
    {
        return new TestCache();
    }

    /**
     * 创建简化的 ContextServiceInterface 测试实现
     */
    private function createTestContextServiceImplementation(): ContextServiceInterface
    {
        return new TestContextService();
    }

    /**
     * 创建测试用的ResponseStreamInterface实现
     */
    private function createTestResponseStream(): ResponseStreamInterface
    {
        return TestEntityGenerator::createResponseStream();
    }

    /**
     * 创建简化的 LoggerInterface 测试实现
     */
    private function createTestLoggerImplementation(): LoggerInterface
    {
        return new TestLoggerWithLevelTracking();
    }

    private function createSmartHttpClient(): void
    {
        // 创建优化的测试实现类
        $this->cache = $this->createTestCacheImplementation();
        $this->contextService = $this->createTestContextServiceImplementation();
        $this->logger = $this->createTestLoggerImplementation();

        // 设置协程支持默认为 false，这样会使用 CurlHttpClient
        if (method_exists($this->contextService, 'setSupportCoroutine')) {
            $this->contextService->setSupportCoroutine(false);
        }

        // @phpstan-ignore-next-line integrationTest.noDirectInstantiationOfCoveredClass - 需要使用匿名类依赖验证行为
        $this->client = new SmartHttpClient($this->cache, $this->contextService, $this->logger);
    }

    /**
     * 使用反射将内部 HttpClient 注入到被测客户端
     */
    private function injectInnerClient(HttpClientInterface $inner): void
    {
        $ref = new \ReflectionClass($this->client);
        $prop = $ref->getProperty('inner');
        $prop->setAccessible(true);
        $prop->setValue($this->client, $inner);
    }

    public function testRefreshDomainResolveCache(): void
    {
        $this->createSmartHttpClient();

        $host = 'example.com';
        $ip = '93.184.216.34';

        // 使用测试数据设置方法
        if (method_exists($this->cache, 'setTestData')) {
            $this->cache->setTestData('api-client-resolve-example.com', $ip);
        }

        $result = $this->client->refreshDomainResolveCache($host);
        $this->assertEquals($ip, $result);

        // 验证缓存被调用
        if (method_exists($this->cache, 'getCallLog')) {
            $callLog = $this->cache->getCallLog();
            $this->assertIsArray($callLog);
            $this->assertArrayHasKey('get', $callLog);
            $getLog = $callLog['get'] ?? [];
            $this->assertIsArray($getLog);
            $this->assertContains('api-client-resolve-example.com', $getLog);
        }
    }

    public function testWithOptions(): void
    {
        $this->createSmartHttpClient();

        $options = ['timeout' => 30];

        $newClient = $this->client->withOptions($options);

        $this->assertNotSame($this->client, $newClient);
        $this->assertNotNull($newClient);
    }

    public function testRequest(): void
    {
        // 创建简化的测试实现
        $mockResponse = new class implements ResponseInterface {
            public function getStatusCode(): int
            {
                return 200;
            }

            /** @return array<string, list<string>> */
            public function getHeaders(bool $throw = true): array
            {
                return [];
            }

            public function getContent(bool $throw = true): string
            {
                return '';
            }

            /** @return array<string, mixed> */
            public function toArray(bool $throw = true): array
            {
                return [];
            }

            public function cancel(): void
            {
            }

            public function getInfo(?string $type = null): mixed
            {
                return null;
            }
        };

        $mockStream = new class implements ResponseStreamInterface {
            public function key(): ResponseInterface
            {
                throw new \LogicException('Not implemented');
            }

            public function current(): ChunkInterface
            {
                throw new \LogicException('Not implemented');
            }

            public function next(): void
            {
            }

            public function rewind(): void
            {
            }

            public function valid(): bool
            {
                return false;
            }
        };

        $mockInnerClient = new class($mockResponse, $mockStream) implements HttpClientInterface {
            public function __construct(
                private readonly ResponseInterface $response,
                private readonly ResponseStreamInterface $stream,
            ) {
            }

            /** @param array<mixed> $options */
            public function request(string $method, string $url, array $options = []): ResponseInterface
            {
                return $this->response;
            }

            public function stream(iterable|ResponseInterface $responses, ?float $timeout = null): ResponseStreamInterface
            {
                return $this->stream;
            }

            /** @param array<mixed> $options */
            public function withOptions(array $options): static
            {
                return $this;
            }
        };

        $this->createSmartHttpClient();

        // 设置协程支持为false，使用CurlHttpClient
        if (method_exists($this->contextService, 'setSupportCoroutine')) {
            $this->contextService->setSupportCoroutine(false);
        }

        // 通过反射注入内部 HttpClient，避免继承 final 类
        $this->injectInnerClient($mockInnerClient);

        $method = 'GET';
        $url = 'https://example.com/test';
        $options = ['headers' => ['Accept' => 'application/json']];

        // 执行请求
        $response = $this->client->request($method, $url, $options);

        // 检查返回的是ResponseInterface实例
        $this->assertInstanceOf(ResponseInterface::class, $response);

        // 验证响应状态码
        $this->assertEquals(200, $response->getStatusCode());

        // 验证日志记录被调用
        if (method_exists($this->logger, 'getLogs') && method_exists($this->logger, 'getLogsByLevel')) {
            $logs = $this->logger->getLogs();
            $debugLogs = $this->logger->getLogsByLevel('debug');
            $infoLogs = $this->logger->getLogsByLevel('info');

            $this->assertIsArray($debugLogs);
            $this->assertIsArray($infoLogs);
            $this->assertGreaterThanOrEqual(2, count($debugLogs), 'Should have at least 2 debug log entries');
            $this->assertGreaterThanOrEqual(1, count($infoLogs), 'Should have at least 1 info log entry');
        }
    }

    public function testStream(): void
    {
        // 创建简化的测试实现
        $mockResponse = new class implements ResponseInterface {
            public function getStatusCode(): int
            {
                return 200;
            }

            /** @return array<string, list<string>> */
            public function getHeaders(bool $throw = true): array
            {
                return [];
            }

            public function getContent(bool $throw = true): string
            {
                return '';
            }

            /** @return array<string, mixed> */
            public function toArray(bool $throw = true): array
            {
                return [];
            }

            public function cancel(): void
            {
            }

            public function getInfo(?string $type = null): mixed
            {
                return null;
            }
        };

        // 创建测试用的ResponseStreamInterface实现
        $mockStream = $this->createTestResponseStream();

        $mockInnerClient = new class($mockResponse, $mockStream) implements HttpClientInterface {
            /** @var array<int, array{responses: iterable<ResponseInterface>|ResponseInterface, timeout: ?float}> */
            private array $streamCalls = [];

            public function __construct(
                private readonly ResponseInterface $response,
                private readonly ResponseStreamInterface $stream,
            ) {
            }

            /** @param array<mixed> $options */
            public function request(string $method, string $url, array $options = []): ResponseInterface
            {
                return $this->response;
            }

            public function stream(iterable|ResponseInterface $responses, ?float $timeout = null): ResponseStreamInterface
            {
                $this->streamCalls[] = ['responses' => $responses, 'timeout' => $timeout];
                if (method_exists($this->stream, 'markStreamMethodCalled')) {
                    $this->stream->markStreamMethodCalled();
                }

                return $this->stream;
            }

            /** @param array<mixed> $options */
            public function withOptions(array $options): static
            {
                return $this;
            }

            /** @return array<int, array{responses: iterable<ResponseInterface>|ResponseInterface, timeout: ?float}> */
            public function getStreamCalls(): array
            {
                return $this->streamCalls;
            }
        };

        $this->createSmartHttpClient();

        // 设置协程支持为false
        if (method_exists($this->contextService, 'setSupportCoroutine')) {
            $this->contextService->setSupportCoroutine(false);
        }

        // 通过反射注入内部 HttpClient，避免继承 final 类
        $this->injectInnerClient($mockInnerClient);

        $responses = [$mockResponse];

        // 测试stream方法不抛出异常
        $stream = $this->client->stream($responses);

        // 检查返回的是ResponseStreamInterface实例
        $this->assertInstanceOf(ResponseStreamInterface::class, $stream);

        // 验证inner client的stream方法被调用
        if (method_exists($mockStream, 'wasStreamMethodCalled')) {
            $this->assertTrue($mockStream->wasStreamMethodCalled(), 'Stream method should have been called');
        }
        $streamCalls = $mockInnerClient->getStreamCalls();
        $this->assertCount(1, $streamCalls, 'Stream method should be called exactly once');
        $this->assertEquals($responses, $streamCalls[0]['responses'], 'Responses should match');
        $this->assertNull($streamCalls[0]['timeout'], 'Timeout should be null');
    }
}
