<?php

namespace HttpClientBundle\Tests\DependencyInjection\Compiler;

use HttpClientBundle\DependencyInjection\Compiler\RemoveUnusedServicePass;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(RemoveUnusedServicePass::class)]
#[RunTestsInSeparateProcesses] final class RemoveUnusedServicePassTest extends AbstractIntegrationTestCase
{
    private RemoveUnusedServicePass $pass;

    private ContainerBuilder $container;

    protected function onSetUp(): void
    {
        // @phpstan-ignore-next-line integrationTest.noDirectInstantiationOfCoveredClass - CompilerPass 需要直接实例化进行测试，因为它们不是容器服务
        $this->pass = new RemoveUnusedServicePass();
        $this->container = new ContainerBuilder();
    }

    public function testProcessRemovesApiRequestServices(): void
    {
        // 创建一个 ApiRequest 子类的定义
        $apiRequestDefinition = new Definition();
        $apiRequestDefinition->setClass(TestApiRequest::class);
        $this->container->setDefinition('test.api_request', $apiRequestDefinition);

        // 创建一个普通服务的定义
        $normalDefinition = new Definition();
        $normalDefinition->setClass(\stdClass::class);
        $this->container->setDefinition('test.normal_service', $normalDefinition);

        $this->pass->process($this->container);

        // ApiRequest 服务应该被移除
        $this->assertFalse($this->container->hasDefinition('test.api_request'));
        // 普通服务应该保留
        $this->assertTrue($this->container->hasDefinition('test.normal_service'));
    }

    public function testProcessIgnoresServicesWithoutClass(): void
    {
        $definition = new Definition();
        // 不设置类名
        $this->container->setDefinition('test.no_class', $definition);

        $this->pass->process($this->container);

        // 服务应该保留
        $this->assertTrue($this->container->hasDefinition('test.no_class'));
    }
}
