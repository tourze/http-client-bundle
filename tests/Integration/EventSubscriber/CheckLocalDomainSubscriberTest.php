<?php

namespace HttpClientBundle\Tests\Integration\EventSubscriber;

use HttpClientBundle\Event\RequestEvent;
use HttpClientBundle\EventSubscriber\CheckLocalDomainSubscriber;
use HttpClientBundle\Exception\LocalDomainRequestException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @covers \HttpClientBundle\EventSubscriber\CheckLocalDomainSubscriber
 */
class CheckLocalDomainSubscriberTest extends TestCase
{
    private CheckLocalDomainSubscriber $subscriber;
    private RequestStack $requestStack;

    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();
        $this->subscriber = new CheckLocalDomainSubscriber($this->requestStack);
    }

    public function testCheckLocalDomainsWithNoCurrentRequest(): void
    {
        $event = new RequestEvent();
        $event->setUrl('https://example.com');
        
        // 应该不抛出异常，因为没有当前请求
        $this->subscriber->checkLocalDomains($event);
        $this->addToAssertionCount(1);
    }

    public function testCheckLocalDomainsWithDifferentHost(): void
    {
        $request = Request::create('https://localhost:8000/test');
        $this->requestStack->push($request);
        
        $event = new RequestEvent();
        $event->setUrl('https://example.com');
        
        // 应该不抛出异常，因为是不同的主机
        $this->subscriber->checkLocalDomains($event);
        $this->addToAssertionCount(1);
    }

    public function testCheckLocalDomainsWithSameHostThrowsException(): void
    {
        $request = Request::create('https://localhost:8000/test');
        $this->requestStack->push($request);
        
        $event = new RequestEvent();
        $event->setUrl('https://localhost:8000');
        
        $this->expectException(LocalDomainRequestException::class);
        $this->expectExceptionMessage('在开发阶段，为了避免HTTP请求时可能造成的进程阻塞，请不要使用 HttpClient 请求 https://localhost:8000 相关地址。');
        
        $this->subscriber->checkLocalDomains($event);
    }

    public function testCheckLocalDomainsWithSameHostPrefixThrowsException(): void
    {
        $request = Request::create('https://localhost:8000/test');
        $this->requestStack->push($request);
        
        $event = new RequestEvent();
        $event->setUrl('https://localhost:8000/api/test');
        
        $this->expectException(LocalDomainRequestException::class);
        $this->expectExceptionMessage('在开发阶段，为了避免HTTP请求时可能造成的进程阻塞，请不要使用 HttpClient 请求 https://localhost:8000 相关地址。');
        
        $this->subscriber->checkLocalDomains($event);
    }
}