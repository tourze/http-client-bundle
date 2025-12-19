<?php

namespace HttpClientBundle\Tests\EventSubscriber;

use HttpClientBundle\Event\RequestEvent;
use HttpClientBundle\EventSubscriber\CheckLocalDomainSubscriber;
use HttpClientBundle\Exception\LocalDomainRequestException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Tourze\PHPUnitSymfonyKernelTest\AbstractEventSubscriberTestCase;

/**
 * @internal
 */
#[CoversClass(CheckLocalDomainSubscriber::class)]
#[RunTestsInSeparateProcesses]
final class CheckLocalDomainSubscriberTest extends AbstractEventSubscriberTestCase
{
    protected function onSetUp(): void
    {
        // 空实现，测试方法中直接从容器获取服务
    }

    public function testCheckLocalDomainsWithNoCurrentRequest(): void
    {
        $subscriber = self::getService(CheckLocalDomainSubscriber::class);

        $event = new RequestEvent();
        $event->setUrl('https://example.com');

        // 应该不抛出异常，因为没有当前请求
        $subscriber->checkLocalDomains($event);

        // 验证事件的URL没有被修改
        self::assertSame('https://example.com', $event->getUrl());
    }

    public function testCheckLocalDomainsWithDifferentHost(): void
    {
        $requestStack = self::getService(RequestStack::class);
        $request = Request::create('https://localhost:8000/test');
        $requestStack->push($request);

        $subscriber = self::getService(CheckLocalDomainSubscriber::class);

        $event = new RequestEvent();
        $event->setUrl('https://example.com');

        // 应该不抛出异常，因为是不同的主机
        $subscriber->checkLocalDomains($event);

        // 验证事件的URL没有被修改
        self::assertSame('https://example.com', $event->getUrl());
    }

    public function testCheckLocalDomainsWithSameHostThrowsException(): void
    {
        $requestStack = self::getService(RequestStack::class);
        $request = Request::create('https://localhost:8000/test');
        $requestStack->push($request);

        $subscriber = self::getService(CheckLocalDomainSubscriber::class);

        $event = new RequestEvent();
        $event->setUrl('https://localhost:8000');

        $this->expectException(LocalDomainRequestException::class);
        $this->expectExceptionMessage('在开发阶段，为了避免HTTP请求时可能造成的进程阻塞，请不要使用 HttpClient 请求 https://localhost:8000 相关地址。');

        $subscriber->checkLocalDomains($event);
    }

    public function testCheckLocalDomainsWithSameHostPrefixThrowsException(): void
    {
        $requestStack = self::getService(RequestStack::class);
        $request = Request::create('https://localhost:8000/test');
        $requestStack->push($request);

        $subscriber = self::getService(CheckLocalDomainSubscriber::class);

        $event = new RequestEvent();
        $event->setUrl('https://localhost:8000/api/test');

        $this->expectException(LocalDomainRequestException::class);
        $this->expectExceptionMessage('在开发阶段，为了避免HTTP请求时可能造成的进程阻塞，请不要使用 HttpClient 请求 https://localhost:8000 相关地址。');

        $subscriber->checkLocalDomains($event);
    }
}
