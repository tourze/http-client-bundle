<?php

namespace HttpClientBundle\Tests\Request;

use HttpClientBundle\Request\ApiRequest;
use PHPUnit\Framework\TestCase;

/**
 * 测试用的具体ApiRequest类实现
 */
class ConcreteApiRequest extends ApiRequest
{
    private string $path;
    private ?array $options;
    private ?string $method;

    public function __construct(string $path, ?array $options = null, ?string $method = null)
    {
        $this->path = $path;
        $this->options = $options;
        $this->method = $method;
    }

    public function getRequestPath(): string
    {
        return $this->path;
    }

    public function getRequestOptions(): ?array
    {
        return $this->options;
    }

    public function getRequestMethod(): ?string
    {
        return $this->method ?? parent::getRequestMethod();
    }
}

/**
 * @covers \HttpClientBundle\Request\ApiRequest
 */
class ApiRequestTest extends TestCase
{
    private ConcreteApiRequest $request;
    private string $path = '/api/test';
    private array $options = ['query' => ['param' => 'value']];
    private string $method = 'POST';

    protected function setUp(): void
    {
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
            'payload' => $this->options
        ], JSON_UNESCAPED_SLASHES);

        $this->assertEquals($expected, (string)$this->request);
    }

    public function testGenerateLogData(): void
    {
        $expected = [
            '_className' => ConcreteApiRequest::class,
            'path' => $this->path,
            'method' => $this->method,
            'payload' => $this->options
        ];

        $this->assertEquals($expected, $this->request->generateLogData());
    }
}
