<?php

namespace HttpClientBundle\Tests\Unit\Event;

use HttpClientBundle\Event\ResponseEvent;
use PHPUnit\Framework\TestCase;

/**
 * @covers \HttpClientBundle\Event\ResponseEvent
 */
class ResponseEventTest extends TestCase
{
    private ResponseEvent $event;

    protected function setUp(): void
    {
        $this->event = new ResponseEvent();
    }

    public function testUrlMethods(): void
    {
        $url = 'https://example.com/api';
        $this->event->setUrl($url);
        $this->assertEquals($url, $this->event->getUrl());
    }

    public function testMethodMethods(): void
    {
        $method = 'POST';
        $this->event->setMethod($method);
        $this->assertEquals($method, $this->event->getMethod());
    }

    public function testOptionsMethods(): void
    {
        $options = ['timeout' => 30, 'headers' => ['Content-Type' => 'application/json']];
        $this->event->setOptions($options);
        $this->assertEquals($options, $this->event->getOptions());
    }

    public function testDurationMethods(): void
    {
        $duration = 1.5;
        $this->event->setDuration($duration);
        $this->assertEquals($duration, $this->event->getDuration());
    }

    public function testDurationWithIntegerValue(): void
    {
        $duration = 2;
        $this->event->setDuration($duration);
        $this->assertEquals($duration, $this->event->getDuration());
    }

    public function testStatusCodeMethods(): void
    {
        $statusCode = 200;
        $this->event->setStatusCode($statusCode);
        $this->assertEquals($statusCode, $this->event->getStatusCode());
    }
}