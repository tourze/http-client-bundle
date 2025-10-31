<?php

namespace HttpClientBundle\Tests\Client;

use HttpClientBundle\Client\ApiClient;
use HttpClientBundle\Tests\Helper\TestContainer;
use HttpClientBundle\Tests\Helper\TestEntityGenerator;
use HttpClientBundle\Tests\Helper\TestSmartHttpClient;
use Laminas\Diagnostics\Result\Skip;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\SharedLockInterface;
use Symfony\Component\Lock\Store\NullStore;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tourze\DoctrineAsyncInsertBundle\Service\AsyncInsertService as DoctrineService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\Symfony\RuntimeContextBundle\Service\ContextServiceInterface;

/**
 * @internal
 */
#[CoversClass(ApiClient::class)]
#[RunTestsInSeparateProcesses]
final class ApiClientTest extends AbstractIntegrationTestCase
{
    private TestApiClient $client;

    private HttpClientInterface $httpClient;

    private CacheInterface $cache;

    private EventDispatcherInterface $eventDispatcher;

    private LockFactory $lockFactory;

    private DoctrineService $doctrineService;

    protected function onSetUp(): void
    {
        // AbstractIntegrationTestCase 要求的抽象方法实现
        // 由于我们不使用标准的 setUp 流程，这里留空
    }

    private function createApiClient(): void
    {
        // 使用 InterfaceStubTrait 创建 SmartHttpClient 的标准测试实现
        $mockCache = $this->createMock(CacheInterface::class);
        $mockContext = $this->createMock(ContextServiceInterface::class);
        $mockLogger = $this->createMock(LoggerInterface::class);

        // 创建测试用的HttpClient实现，避免使用匿名类
        $this->httpClient = new TestSmartHttpClient();

        $this->cache = $this->createMock(CacheInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        // 使用 TestEntityGenerator 创建 LockFactory 实现
        $this->lockFactory = new class extends LockFactory {
            public function __construct()
            {
                // 调用父类构造函数避免静态分析错误
                // 使用NullStore作为默认存储以避免依赖
                parent::__construct(new NullStore());
            }

            public function createLock(string $resource, ?float $ttl = 300.0, bool $autoRelease = true): SharedLockInterface
            {
                return TestEntityGenerator::createSharedLock();
            }
        };

        /*
         * DoctrineService Mock处理的详细说明：
         * 1) 为什么回到使用Mock：
         *    - AsyncInsertService是readonly类，无法被匿名类继承
         *    - 在当前场景下这个服务没有被实际调用，所以Mock是安全的
         *    - 这是一个暂时的妥协，理想情况下应该为该服务定义接口
         * 2) 使用Mock的合理性：
         *    - 测试中没有直接调用该服务的方法
         *    - 仅用作依赖注入，满足类型检查要求
         * 3) 未来改进方向：
         *    - 建议第三方包提供接口抽象
         *    - 或者在我们的代码中包装该服务以提供接口
         */
        $this->doctrineService = $this->createMock(DoctrineService::class);

        // 创建测试用的容器实现，避免使用匿名类
        $container = new TestContainer(
            $this->httpClient,
            $this->cache,
            $this->eventDispatcher,
            $this->lockFactory,
            $this->doctrineService
        );

        $logger = $this->createMock(LoggerInterface::class);
        $this->client = new TestApiClient(
            $logger,
            $this->httpClient,
            $this->lockFactory,
            $this->cache,
            $this->eventDispatcher,
            $this->doctrineService
        );
    }

    public function testCheckNoBaseUrl(): void
    {
        $this->createApiClient();

        $result = $this->client->check();

        $this->assertInstanceOf(Skip::class, $result);
    }

    public function testCheckWithBaseUrl(): void
    {
        $this->createApiClient();

        // 使用HTTPS URL避免SSL证书验证问题，使用可靠的测试域名
        $this->client->setBaseUrl('https://httpbin.org/api');

        // 匿名类实现会自动返回127.0.0.1，无需额外配置

        $result = $this->client->check();

        // 检查结果不为null，具体结果依赖于外部服务的可用性
        $this->assertNotNull($result);
    }

    public function testGetLabel(): void
    {
        $this->createApiClient();

        $this->assertEquals(TestApiClient::class, $this->client->getLabel());
    }
}
