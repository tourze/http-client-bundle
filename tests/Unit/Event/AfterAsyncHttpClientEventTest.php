<?php

namespace HttpClientBundle\Tests\Unit\Event;

use HttpClientBundle\Event\AfterAsyncHttpClientEvent;
use PHPUnit\Framework\TestCase;

/**
 * @covers \HttpClientBundle\Event\AfterAsyncHttpClientEvent
 */
class AfterAsyncHttpClientEventTest extends TestCase
{
    private AfterAsyncHttpClientEvent $event;

    protected function setUp(): void
    {
        $this->event = new AfterAsyncHttpClientEvent();
    }

    public function testResultMethods(): void
    {
        $result = 'test result';
        $this->event->setResult($result);
        $this->assertEquals($result, $this->event->getResult());
    }

    public function testResultDefaultValue(): void
    {
        $this->assertEquals('', $this->event->getResult());
    }

    public function testParamsMethods(): void
    {
        $params = ['key1' => 'value1', 'key2' => 'value2'];
        $this->event->setParams($params);
        $this->assertEquals($params, $this->event->getParams());
    }

    public function testParamsDefaultValue(): void
    {
        $this->assertEquals([], $this->event->getParams());
    }
}