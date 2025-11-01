<?php

namespace HttpClientBundle\Tests\Request;

use HttpClientBundle\Request\ApiRequest;
use HttpClientBundle\Test\RequestTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * @internal
 */
#[CoversClass(ApiRequest::class)]
final class ApiRequestTest extends RequestTestCase
{
    private ConcreteApiRequest $request;

    private string $path = '/api/test';

    /** @var array<string,mixed> */
    private array $options = ['query' => ['param' => 'value']];

    private string $method = 'POST';

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new ConcreteApiRequest($this->path, $this->options, $this->method);
    }

    public function testGetRequestPath(): void
    {
        $this->assertEquals($this->path, $this->request->getRequestPath());
    }

    public function testGetRequestOptions(): void
    {
        $this->assertEquals($this->options, $this->request->getRequestOptions());
    }

    public function testGetRequestMethod(): void
    {
        $this->assertEquals($this->method, $this->request->getRequestMethod());
    }

    public function testDefaultMethod(): void
    {
        $request = new ConcreteApiRequest($this->path, $this->options);
        $this->assertNull($request->getRequestMethod());
    }

    public function testToString(): void
    {
        $expected = json_encode([
            '_className' => ConcreteApiRequest::class,
            'path' => $this->path,
            'method' => $this->method,
            'payload' => $this->options,
        ], JSON_UNESCAPED_SLASHES);

        $this->assertEquals($expected, (string) $this->request);
    }

    public function testGenerateLogData(): void
    {
        $expected = [
            '_className' => ConcreteApiRequest::class,
            'path' => $this->path,
            'method' => $this->method,
            'payload' => $this->options,
        ];

        $this->assertEquals($expected, $this->request->generateLogData());
    }
}
