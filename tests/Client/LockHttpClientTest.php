<?php

declare(strict_types=1);

namespace HttpClientBundle\Tests\Client;

use HttpClientBundle\Client\LockHttpClient;
use HttpClientBundle\Exception\LockTimeoutHttpClientException;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Lock\LockFactory;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

/**
 * LockHttpClient 单元测试
 *
 * LockHttpClient 是一个工具类/装饰器，不作为服务注册到容器。
 * 使用单元测试模式，Mock HttpClient（网络请求）。
 *
 * @internal
 */
#[CoversClass(LockHttpClient::class)]
final class LockHttpClientTest extends AbstractClientTestCase
{
    private LockHttpClient $client;

    /** @var HttpClientInterface&\PHPUnit\Framework\MockObject\MockObject */
    private HttpClientInterface $innerClient;

    private LockFactory $lockFactory;

    /** 用于测试的锁获取计数器 */
    public int $lockAcquireCount = 0;

    /** 用于测试的锁释放计数器 */
    public int $lockReleaseCount = 0;

    /** 控制锁是否能成功获取 */
    public bool $lockShouldSucceed = true;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock HttpClient（网络请求可以 Mock）
        $this->innerClient = $this->createMock(HttpClientInterface::class);

        // 创建自定义的 LockFactory 用于测试，使用计数器而非 Mock
        $this->lockFactory = $this->createTestLockFactory();

        // 创建 LockHttpClient 实例
        $this->client = new LockHttpClient($this->innerClient, $this->lockFactory);

        // 重置计数器
        $this->lockAcquireCount = 0;
        $this->lockReleaseCount = 0;
        $this->lockShouldSucceed = true;
    }

    /**
     * 创建测试用的 LockFactory，内部使用计数器追踪锁操作
     */
    private function createTestLockFactory(): LockFactory
    {
        return new TestLockFactory($this);
    }

    public function testRequestWithoutLocking(): void
    {
        $method = 'GET';
        $url = 'https://example.com';
        $options = ['timeout' => 30];

        $mockResponse = $this->createMock(ResponseInterface::class);

        $this->innerClient->expects(self::once())
            ->method('request')
            ->with($method, $url, $options)
            ->willReturn($mockResponse)
        ;

        // 重置计数器
        $this->lockAcquireCount = 0;
        $this->lockReleaseCount = 0;

        $response = $this->client->request($method, $url, $options);

        $this->assertSame($mockResponse, $response);
        // 验证没有创建锁
        $this->assertEquals(0, $this->lockAcquireCount);
        $this->assertEquals(0, $this->lockReleaseCount);
    }

    public function testRequestWithLockingSuccess(): void
    {
        $method = 'GET';
        $url = 'https://example.com';
        $lockName = 'test-lock';
        $options = [
            'lock_key' => $lockName,
            'timeout' => 30,
        ];

        $mockResponse = $this->createMock(ResponseInterface::class);

        $this->innerClient->expects(self::once())
            ->method('request')
            ->with($method, $url, self::logicalNot(self::arrayHasKey('lock_key')))
            ->willReturn($mockResponse)
        ;

        // 设置锁应该成功
        $this->lockShouldSucceed = true;
        // 重置计数器
        $this->lockAcquireCount = 0;
        $this->lockReleaseCount = 0;

        $response = $this->client->request($method, $url, $options);

        $this->assertSame($mockResponse, $response);
        // 验证锁的操作
        $this->assertEquals(1, $this->lockAcquireCount, '应该尝试获取锁一次');
        $this->assertEquals(1, $this->lockReleaseCount, '应该释放锁一次');
    }

    public function testRequestWithLockingFailure(): void
    {
        $method = 'GET';
        $url = 'https://example.com';
        $lockName = 'test-lock';
        $options = [
            'lock_key' => $lockName,
        ];

        $this->innerClient->expects(self::never())
            ->method('request')
        ;

        // 设置锁应该失败
        $this->lockShouldSucceed = false;
        // 重置计数器
        $this->lockAcquireCount = 0;
        $this->lockReleaseCount = 0;

        $this->expectException(LockTimeoutHttpClientException::class);

        try {
            $this->client->request($method, $url, $options);
        } finally {
            // 验证锁的操作（异常情况下的验证）
            $this->assertEquals(1, $this->lockAcquireCount, '应该尝试获取锁一次');
            $this->assertEquals(0, $this->lockReleaseCount, '失败时不应释放锁');
        }
    }

    public function testWithOptions(): void
    {
        $options = ['timeout' => 30];

        $newInnerClient = $this->createMock(HttpClientInterface::class);

        $this->innerClient->expects(self::once())
            ->method('withOptions')
            ->with($options)
            ->willReturn($newInnerClient)
        ;

        $newClient = $this->client->withOptions($options);

        $this->assertNotSame($this->client, $newClient);
        $this->assertInstanceOf(LockHttpClient::class, $newClient);
    }

    public function testStream(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $responses = [$mockResponse];
        $timeout = 10.0;

        $mockStream = $this->createMock(ResponseStreamInterface::class);

        $this->innerClient->expects(self::once())
            ->method('stream')
            ->with($responses, $timeout)
            ->willReturn($mockStream)
        ;

        $result = $this->client->stream($responses, $timeout);

        $this->assertSame($mockStream, $result);
    }
}
