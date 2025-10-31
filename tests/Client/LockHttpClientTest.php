<?php

namespace HttpClientBundle\Tests\Client;

use HttpClientBundle\Client\LockHttpClient;
use HttpClientBundle\Exception\LockTimeoutHttpClientException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\SharedLockInterface;
use Symfony\Component\Lock\Store\NullStore;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(LockHttpClient::class)]
#[RunTestsInSeparateProcesses]
final class LockHttpClientTest extends AbstractIntegrationTestCase
{
    private LockHttpClient $client;

    /** @var HttpClientInterface&MockObject */
    private HttpClientInterface $innerClient;

    private LockFactory $lockFactory;

    public bool $lockShouldSucceed = true;

    public int $lockCreateCallCount = 0;

    public int $lockAcquireCallCount = 0;

    public int $lockReleaseCallCount = 0;

    protected function onSetUp(): void
    {
        // AbstractIntegrationTestCase 要求的抽象方法实现
        // 由于我们不使用标准的 setUp 流程，这里留空
    }

    private function createLockHttpClient(): void
    {
        /** @var HttpClientInterface&MockObject $innerClient */
        $innerClient = $this->createMock(HttpClientInterface::class);
        $this->innerClient = $innerClient;

        // 使用简化的 LockFactory 实现，配合 TestEntityGenerator
        $this->lockFactory = new class($this) extends LockFactory {
            public function __construct(private LockHttpClientTest $testCase)
            {
                parent::__construct(new NullStore());
            }

            public function createLock(string $resource, ?float $ttl = 300.0, bool $autoRelease = true): SharedLockInterface
            {
                ++$this->testCase->lockCreateCallCount;

                return new TestSharedLockWithCounters($this->testCase);
            }
        };

        // @phpstan-ignore-next-line integrationTest.noDirectInstantiationOfCoveredClass - 需要使用Mock依赖验证行为
        $this->client = new LockHttpClient($this->innerClient, $this->lockFactory);
    }

    public function testRequestWithoutLocking(): void
    {
        $this->createLockHttpClient();

        $method = 'GET';
        $url = 'https://example.com';
        /** @var array<string, mixed> */
        $options = ['timeout' => 30];

        $response = $this->createMock(ResponseInterface::class);

        $this->innerClient->expects(self::once())
            ->method('request')
            ->with($method, $url, $options)
            ->willReturn($response)
        ;

        // 重置计数器
        $this->lockCreateCallCount = 0;

        $result = $this->client->request($method, $url, $options);

        $this->assertSame($response, $result);
        // 验证没有创建锁
        $this->assertEquals(0, $this->lockCreateCallCount);
    }

    public function testRequestWithLockingSuccess(): void
    {
        $this->createLockHttpClient();

        $method = 'GET';
        $url = 'https://example.com';
        $lockName = 'test-lock';
        $options = [
            'lock_key' => $lockName,
            'timeout' => 30,
        ];
        $optionsNoLock = self::logicalNot(self::arrayHasKey('lock_key'));

        $response = $this->createMock(ResponseInterface::class);

        // 设置锁应该成功
        $this->lockShouldSucceed = true;
        // 重置计数器
        $this->lockCreateCallCount = 0;
        $this->lockAcquireCallCount = 0;
        $this->lockReleaseCallCount = 0;

        $this->innerClient->expects(self::once())
            ->method('request')
            ->with($method, $url, $optionsNoLock)
            ->willReturn($response)
        ;

        $result = $this->client->request($method, $url, $options);

        $this->assertSame($response, $result);
        // 验证锁的操作
        $this->assertEquals(1, $this->lockCreateCallCount);
        $this->assertEquals(1, $this->lockAcquireCallCount);
        $this->assertEquals(1, $this->lockReleaseCallCount);
    }

    public function testRequestWithLockingFailure(): void
    {
        $this->createLockHttpClient();

        $method = 'GET';
        $url = 'https://example.com';
        $lockName = 'test-lock';
        $options = [
            'lock_key' => $lockName,
        ];

        // 设置锁应该失败
        $this->lockShouldSucceed = false;
        // 重置计数器
        $this->lockCreateCallCount = 0;
        $this->lockAcquireCallCount = 0;
        $this->lockReleaseCallCount = 0;

        $this->innerClient->expects(self::never())
            ->method('request')
        ;

        $this->expectException(LockTimeoutHttpClientException::class);

        try {
            $this->client->request($method, $url, $options);
        } finally {
            // 验证锁的操作（异常情况下的验证）
            $this->assertEquals(1, $this->lockCreateCallCount);
            $this->assertEquals(1, $this->lockAcquireCallCount);
            $this->assertEquals(0, $this->lockReleaseCallCount);
        }
    }

    public function testWithOptions(): void
    {
        $this->createLockHttpClient();

        /** @var array<string, mixed> */
        $options = ['timeout' => 30];

        $newInnerClient = $this->createMock(HttpClientInterface::class);

        $this->innerClient->expects(self::once())
            ->method('withOptions')
            ->with($options)
            ->willReturn($newInnerClient)
        ;

        $newClient = $this->client->withOptions($options);

        $this->assertNotSame($this->client, $newClient);
    }

    public function testStream(): void
    {
        $this->createLockHttpClient();

        $responses = [
            $this->createMock(ResponseInterface::class),
            $this->createMock(ResponseInterface::class),
        ];
        $timeout = 10.0;

        $expectedStream = $this->createMock(ResponseStreamInterface::class);

        $this->innerClient->expects(self::once())
            ->method('stream')
            ->with($responses, $timeout)
            ->willReturn($expectedStream)
        ;

        $result = $this->client->stream($responses, $timeout);

        $this->assertSame($expectedStream, $result);
    }
}
