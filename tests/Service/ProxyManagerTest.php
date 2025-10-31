<?php

declare(strict_types=1);

namespace HttpClientBundle\Tests\Service;

use HttpClientBundle\Service\ProxyManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(ProxyManager::class)]
#[RunTestsInSeparateProcesses]
class ProxyManagerTest extends AbstractIntegrationTestCase
{
    private ProxyManager $proxyManager;

    protected function onSetUp(): void
    {
        // 从容器中获取服务实例，符合集成测试规范
        $this->proxyManager = self::getService(ProxyManager::class);
    }

    public function testApplyProxySettingsWithNoProxy(): void
    {
        $url = 'https://example.com';
        $options = ['timeout' => 30];

        $result = $this->proxyManager->applyProxySettings($url, $options);

        $this->assertSame($options, $result);
    }

    public function testApplyProxySettingsWithEmptyProxyDomains(): void
    {
        $_ENV['HTTP_REQUEST_PROXY'] = 'http://proxy.example.com:8080';
        $_ENV['HTTP_REQUEST_PROXY_DOMAINS'] = '';

        $url = 'https://example.com';
        $options = ['timeout' => 30];

        $result = $this->proxyManager->applyProxySettings($url, $options);

        $this->assertSame($options, $result);

        unset($_ENV['HTTP_REQUEST_PROXY'], $_ENV['HTTP_REQUEST_PROXY_DOMAINS']);
    }
}
