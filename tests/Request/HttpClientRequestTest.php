<?php

namespace HttpClientBundle\Tests\Request;

use HttpClientBundle\Request\HttpClientRequest;
use HttpClientBundle\Test\RequestTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * @internal
 */
#[CoversClass(HttpClientRequest::class)]
final class HttpClientRequestTest extends RequestTestCase
{
    private HttpClientRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new HttpClientRequest();
    }

    public function testImplementsRequestInterface(): void
    {
        $this->assertNotNull($this->request);
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
