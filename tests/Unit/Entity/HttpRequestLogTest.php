<?php

namespace HttpClientBundle\Tests\Unit\Entity;

use HttpClientBundle\Entity\HttpRequestLog;
use PHPUnit\Framework\TestCase;

/**
 * @covers \HttpClientBundle\Entity\HttpRequestLog
 */
class HttpRequestLogTest extends TestCase
{
    private HttpRequestLog $entity;

    protected function setUp(): void
    {
        $this->entity = new HttpRequestLog();
    }

    public function testGetId(): void
    {
        $this->assertEquals(0, $this->entity->getId());
    }

    public function testToStringWithId(): void
    {
        $reflectionProperty = new \ReflectionProperty(HttpRequestLog::class, 'id');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->entity, 123);

        $this->entity->setRequestUrl('https://example.com');
        
        $this->assertEquals('HTTP Request Log #123 - https://example.com', $this->entity->__toString());
    }

    public function testToStringWithDefaultId(): void
    {
        $this->assertEquals('HTTP Request Log #0 - Unknown URL', $this->entity->__toString());
    }

    public function testContentMethods(): void
    {
        $content = 'test content';
        $this->entity->setContent($content);
        $this->assertEquals($content, $this->entity->getContent());
    }

    public function testResponseMethods(): void
    {
        $response = 'test response';
        $this->entity->setResponse($response);
        $this->assertEquals($response, $this->entity->getResponse());
    }

    public function testExceptionMethods(): void
    {
        $exception = 'test exception';
        $this->entity->setException($exception);
        $this->assertEquals($exception, $this->entity->getException());
    }

    public function testRenderStatusWithException(): void
    {
        $this->entity->setException('some error');
        $this->assertEquals('异常', $this->entity->renderStatus());
    }

    public function testRenderStatusWithoutException(): void
    {
        $this->assertEquals('成功', $this->entity->renderStatus());
    }

    public function testRequestUrlMethods(): void
    {
        $url = 'https://example.com';
        $this->entity->setRequestUrl($url);
        $this->assertEquals($url, $this->entity->getRequestUrl());
    }

    public function testMethodMethods(): void
    {
        $method = 'POST';
        $this->entity->setMethod($method);
        $this->assertEquals($method, $this->entity->getMethod());
    }

    public function testStopwatchDurationMethods(): void
    {
        $duration = '1.23';
        $this->entity->setStopwatchDuration($duration);
        $this->assertEquals($duration, $this->entity->getStopwatchDuration());
    }

    public function testRequestOptionsMethods(): void
    {
        $options = ['timeout' => 30];
        $this->entity->setRequestOptions($options);
        $this->assertEquals($options, $this->entity->getRequestOptions());
    }

    public function testCreatedFromIpMethods(): void
    {
        $ip = '192.168.1.1';
        $this->entity->setCreatedFromIp($ip);
        $this->assertEquals($ip, $this->entity->getCreatedFromIp());
    }

    public function testCreatedFromUaMethods(): void
    {
        $ua = 'Mozilla/5.0';
        $this->entity->setCreatedFromUa($ua);
        $this->assertEquals($ua, $this->entity->getCreatedFromUa());
    }
}