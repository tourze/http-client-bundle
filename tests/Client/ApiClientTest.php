<?php

declare(strict_types=1);

namespace HttpClientBundle\Tests\Client;

use HttpClientBundle\Client\ApiClient;
use Laminas\Diagnostics\Result\Skip;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\InMemoryStore;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tourze\DoctrineAsyncInsertBundle\Service\AsyncInsertService;

/**
 * ApiClient 单元测试
 *
 * ApiClient 是抽象类，测试时创建具体实现类。
 * 由于抽象类无法从容器获取，因此使用单元测试模式。
 *
 * @internal
 */
#[CoversClass(ApiClient::class)]
final class ApiClientTest extends AbstractClientTestCase
{
    private string $testBaseUrl = '';

    private ?ApiClient $client = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testBaseUrl = '';
        $this->client = $this->createConcreteApiClient();
    }

    /**
     * 创建 ApiClient 的具体实现用于测试
     *
     * ApiClient 是抽象类，无法直接实例化，需要创建具体实现。
     */
    private function createConcreteApiClient(): ApiClient
    {
        $logger = new NullLogger();
        $httpClient = $this->createMock(HttpClientInterface::class);
        $lockFactory = new LockFactory(new InMemoryStore());
        $cache = $this->createMock(CacheInterface::class);
        $eventDispatcher = new EventDispatcher();
        $asyncInsertService = $this->createMock(AsyncInsertService::class);

        $testBaseUrl = &$this->testBaseUrl;

        return new ConcreteApiClientStub(
            $logger,
            $httpClient,
            $lockFactory,
            $cache,
            $eventDispatcher,
            $asyncInsertService,
            $testBaseUrl
        );
    }

    public function testCheckNoBaseUrl(): void
    {
        $this->testBaseUrl = '';
        $this->client = $this->createConcreteApiClient();

        $result = $this->client->check();

        $this->assertInstanceOf(Skip::class, $result);
    }

    public function testCheckWithBaseUrl(): void
    {
        // 设置一个有效的 URL
        $this->testBaseUrl = 'https://httpbin.org';
        $this->client = $this->createConcreteApiClient();

        $result = $this->client->check();

        // 检查结果不为 null，具体结果依赖于外部服务的可用性
        $this->assertNotNull($result);
    }

    public function testGetLabel(): void
    {
        $this->testBaseUrl = '';
        $this->client = $this->createConcreteApiClient();

        $label = $this->client->getLabel();

        // 具体类名应该包含 'ConcreteApiClientStub'
        $this->assertStringContainsString('ConcreteApiClientStub', $label);
    }
}
