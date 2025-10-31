<?php

declare(strict_types=1);

namespace HttpClientBundle\Tests\Service;

use HttpClientBundle\Service\HealthChecker;
use Laminas\Diagnostics\Result\Skip;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @internal
 */
#[CoversClass(HealthChecker::class)]
class HealthCheckerTest extends TestCase
{
    private HealthChecker $healthChecker;

    protected function setUp(): void
    {
        $this->healthChecker = new HealthChecker();
    }

    public function testCheckWithEmptyBaseUrl(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);

        $result = $this->healthChecker->check('', $httpClient);

        $this->assertInstanceOf(Skip::class, $result);
        $this->assertSame('未实现getBaseUrl，不处理', $result->getMessage());
    }

    public function testCheckWithUnsupportedHttpClient(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);

        $result = $this->healthChecker->check('https://example.com', $httpClient);

        $this->assertInstanceOf(Skip::class, $result);
        $this->assertSame('HTTP客户端不支持域名解析缓存功能', $result->getMessage());
    }
}
