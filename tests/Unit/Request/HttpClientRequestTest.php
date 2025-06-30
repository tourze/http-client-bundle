<?php

namespace HttpClientBundle\Tests\Unit\Request;

use HttpClientBundle\Request\HttpClientRequest;
use HttpClientBundle\Request\RequestInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \HttpClientBundle\Request\HttpClientRequest
 */
class HttpClientRequestTest extends TestCase
{
    private HttpClientRequest $request;

    protected function setUp(): void
    {
        $this->request = new HttpClientRequest();
    }

    public function testImplementsRequestInterface(): void
    {
        $this->assertInstanceOf(RequestInterface::class, $this->request);
    }

    public function testRequestPathMethods(): void
    {
        $path = '/api/users';
        $this->request->setRequestPath($path);
        $this->assertEquals($path, $this->request->getRequestPath());
    }

    public function testRequestOptionsMethods(): void
    {
        $options = ['timeout' => 30, 'headers' => ['Content-Type' => 'application/json']];
        $this->request->setRequestOptions($options);
        $this->assertEquals($options, $this->request->getRequestOptions());
    }

    public function testRequestOptionsDefaultValue(): void
    {
        $this->assertNull($this->request->getRequestOptions());
    }

    public function testRequestMethodMethods(): void
    {
        $method = 'POST';
        $this->request->setRequestMethod($method);
        $this->assertEquals($method, $this->request->getRequestMethod());
    }

    public function testRequestMethodDefaultValue(): void
    {
        $this->assertNull($this->request->getRequestMethod());
    }

    public function testSettingNullValues(): void
    {
        // 设置值后再设置为 null
        $this->request->setRequestOptions(['test' => 'value']);
        $this->request->setRequestOptions(null);
        $this->assertNull($this->request->getRequestOptions());

        $this->request->setRequestMethod('GET');
        $this->request->setRequestMethod(null);
        $this->assertNull($this->request->getRequestMethod());
    }
}