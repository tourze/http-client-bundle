<?php

namespace HttpClientBundle\Tests\Event;

use HttpClientBundle\Event\RequestEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitSymfonyUnitTest\AbstractEventTestCase;

/**
 * @internal
 */
#[CoversClass(RequestEvent::class)]
final class RequestEventTest extends AbstractEventTestCase
{
    private RequestEvent $event;

    protected function setUp(): void
    {
        parent::setUp();
        $this->event = new RequestEvent();
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
}
