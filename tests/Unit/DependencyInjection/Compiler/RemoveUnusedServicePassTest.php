<?php

namespace HttpClientBundle\Tests\Unit\DependencyInjection\Compiler;

use HttpClientBundle\DependencyInjection\Compiler\RemoveUnusedServicePass;
use HttpClientBundle\Request\ApiRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @covers \HttpClientBundle\DependencyInjection\Compiler\RemoveUnusedServicePass
 */
class RemoveUnusedServicePassTest extends TestCase
{
    private RemoveUnusedServicePass $pass;
    private ContainerBuilder $container;

    protected function setUp(): void
    {
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

// 测试用的 ApiRequest 子类
class TestApiRequest extends ApiRequest
{
    public function getUrl(): string
    {
        return 'https://example.com/test';
    }

    public function getRequestPath(): string
    {
        return '/test';
    }

    public function getRequestOptions(): ?array
    {
        return [];
    }
}