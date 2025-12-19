<?php

namespace HttpClientBundle\Tests\Service;

use HttpClientBundle\Service\SmartHttpClient;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\ChunkInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\Symfony\RuntimeContextBundle\Service\ContextServiceInterface;

/**
 * 集成测试：SmartHttpClient
 *
 * 测试策略：
 * - 使用容器注入的真实服务（CacheInterface、ContextServiceInterface、LoggerInterface）
 * - 仅 Mock 网络请求层（HttpClientInterface）
 * - 不依赖任何测试 Stub
 *
 * @internal
 */
#[CoversClass(SmartHttpClient::class)]
#[RunTestsInSeparateProcesses]
final class SmartHttpClientTest extends AbstractIntegrationTestCase
{
    private SmartHttpClient $client;

    private CacheInterface $cache;

    private ContextServiceInterface $contextService;

    protected function onSetUp(): void
    {
        // 从容器获取 SmartHttpClient（依赖会自动注入）
        $this->client = self::getService(SmartHttpClient::class);

        // 获取依赖服务（用于测试验证）
        $this->cache = self::getService(CacheInterface::class);
        $this->contextService = self::getService(ContextServiceInterface::class);

        // 设置协程支持为 false，强制使用 CurlHttpClient
        if (method_exists($this->contextService, 'setSupportCoroutine')) {
            $this->contextService->setSupportCoroutine(false);
        }
    }

    /**
     * 使用反射将内部 HttpClient 注入到被测客户端
     */
    private function injectInnerClient(HttpClientInterface $inner): void
    {
        $ref = new \ReflectionClass($this->client);
        $prop = $ref->getProperty('inner');
        $prop->setValue($this->client, $inner);
    }

    /**
     * 创建 Mock Response（匿名类实现）
     */
    private function createMockResponse(int $statusCode = 200, string $content = ''): ResponseInterface
    {
        return new class($statusCode, $content) implements ResponseInterface {
            public function __construct(
                private readonly int $statusCode,
                private readonly string $content,
            ) {
            }

            public function getStatusCode(): int
            {
                return $this->statusCode;
            }

            /** @return array<string, list<string>> */
            public function getHeaders(bool $throw = true): array
            {
                return [];
            }

            public function getContent(bool $throw = true): string
            {
                return $this->content;
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
                if ('http_code' === $type) {
                    return $this->statusCode;
                }

                return null;
            }
        };
    }

    /**
     * 创建 Mock ResponseStream（匿名类实现）
     */
    private function createMockResponseStream(): ResponseStreamInterface
    {
        return new class implements ResponseStreamInterface {
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
    }

    /**
     * 测试：refreshDomainResolveCache - DNS 缓存功能
     */
    public function testRefreshDomainResolveCache(): void
    {
        $host = 'example.com';

        // 第一次调用：会触发 DNS 解析并缓存
        $ip1 = $this->client->refreshDomainResolveCache($host);
        $this->assertNotEmpty($ip1);

        // 第二次调用：应从缓存读取，返回相同结果
        $ip2 = $this->client->refreshDomainResolveCache($host);
        $this->assertEquals($ip1, $ip2);
    }

    /**
     * 测试：withOptions - 返回新实例
     */
    public function testWithOptions(): void
    {
        $options = ['timeout' => 30];

        $newClient = $this->client->withOptions($options);

        $this->assertNotSame($this->client, $newClient);
        $this->assertInstanceOf(SmartHttpClient::class, $newClient);
    }

    /**
     * 测试：request - HTTP 请求转发
     */
    public function testRequest(): void
    {
        $mockResponse = $this->createMockResponse(200, 'test content');
        $mockStream = $this->createMockResponseStream();

        // 创建追踪请求调用的 Mock HttpClient
        $requestCalls = [];
        $mockInnerClient = new class($mockResponse, $mockStream, $requestCalls) implements HttpClientInterface {
            public function __construct(
                private readonly ResponseInterface $response,
                private readonly ResponseStreamInterface $stream,
                private array &$requestCalls,
            ) {
            }

            /** @param array<mixed> $options */
            public function request(string $method, string $url, array $options = []): ResponseInterface
            {
                $this->requestCalls[] = ['method' => $method, 'url' => $url, 'options' => $options];

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

        // 注入 Mock HttpClient
        $this->injectInnerClient($mockInnerClient);

        $method = 'GET';
        $url = 'https://example.com/test';
        $options = ['headers' => ['Accept' => 'application/json']];

        // 执行请求
        $response = $this->client->request($method, $url, $options);

        // 验证：返回 ResponseInterface 实例
        $this->assertInstanceOf(ResponseInterface::class, $response);

        // 验证：状态码正确
        $this->assertEquals(200, $response->getStatusCode());

        // 验证：内容正确
        $this->assertEquals('test content', $response->getContent());

        // 验证：inner client 的 request 方法被调用
        $this->assertCount(1, $requestCalls);
        $this->assertEquals($method, $requestCalls[0]['method']);
        $this->assertEquals($url, $requestCalls[0]['url']);
    }

    /**
     * 测试：stream - 流式响应转发
     */
    public function testStream(): void
    {
        $mockResponse = $this->createMockResponse(200);
        $mockStream = $this->createMockResponseStream();

        // 创建追踪 stream 调用的 Mock HttpClient
        $streamCalls = [];
        $mockInnerClient = new class($mockResponse, $mockStream, $streamCalls) implements HttpClientInterface {
            public function __construct(
                private readonly ResponseInterface $response,
                private readonly ResponseStreamInterface $stream,
                private array &$streamCalls,
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

                return $this->stream;
            }

            /** @param array<mixed> $options */
            public function withOptions(array $options): static
            {
                return $this;
            }
        };

        // 注入 Mock HttpClient
        $this->injectInnerClient($mockInnerClient);

        $responses = [$mockResponse];

        // 执行 stream 方法
        $stream = $this->client->stream($responses);

        // 验证：返回 ResponseStreamInterface 实例
        $this->assertInstanceOf(ResponseStreamInterface::class, $stream);

        // 验证：inner client 的 stream 方法被调用
        $this->assertCount(1, $streamCalls);
        $this->assertEquals($responses, $streamCalls[0]['responses']);
        $this->assertNull($streamCalls[0]['timeout']);
    }
}
