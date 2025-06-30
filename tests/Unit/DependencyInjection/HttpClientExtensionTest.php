<?php

namespace HttpClientBundle\Tests\Unit\DependencyInjection;

use HttpClientBundle\DependencyInjection\HttpClientExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @covers \HttpClientBundle\DependencyInjection\HttpClientExtension
 */
class HttpClientExtensionTest extends TestCase
{
    private HttpClientExtension $extension;
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->extension = new HttpClientExtension();
        $this->container = new ContainerBuilder();
    }

    public function testLoad(): void
    {
        $configs = [];
        
        $this->extension->load($configs, $this->container);
        
        // 验证容器已经加载了服务定义
        $this->assertNotEmpty($this->container->getDefinitions());
    }
}