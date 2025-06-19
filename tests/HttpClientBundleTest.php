<?php

namespace HttpClientBundle\Tests;

use HttpClientBundle\DependencyInjection\Compiler\RemoveUnusedServicePass;
use HttpClientBundle\HttpClientBundle;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @covers \HttpClientBundle\HttpClientBundle
 */
class HttpClientBundleTest extends TestCase
{
    private HttpClientBundle $bundle;
    private ContainerBuilder|MockObject $container;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerBuilder::class);
        $this->bundle = new HttpClientBundle();
    }

    public function testBuild(): void
    {
        // 验证 build 方法调用了父类的 build 方法并添加了编译器传递
        $this->container->expects($this->once())
            ->method('addCompilerPass')
            ->with($this->isInstanceOf(RemoveUnusedServicePass::class));

        $this->bundle->build($this->container);
    }
}
