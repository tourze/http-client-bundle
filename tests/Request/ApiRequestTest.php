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
    private string $path = '/api/test';

    /** @var array<string,mixed> */
    private array $options = ['query' => ['param' => 'value']];

    private string $method = 'POST';

    /**
     * 创建一个用于测试的 ApiRequest 具体实现
     *
     * @param array<string,mixed>|null $options
     */
    private function createRequest(string $path, ?array $options, ?string $method = null): ApiRequest
    {
        return new class($path, $options, $method) extends ApiRequest {
            public function __construct(
                private readonly string $path,
                private readonly ?array $options,
                private readonly ?string $method = null
            ) {
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
                return $this->method;
            }
        };
    }

    public function testGetRequestPath(): void
    {
        $request = $this->createRequest($this->path, $this->options, $this->method);
        $this->assertEquals($this->path, $request->getRequestPath());
    }

    public function testGetRequestOptions(): void
    {
        $request = $this->createRequest($this->path, $this->options, $this->method);
        $this->assertEquals($this->options, $request->getRequestOptions());
    }

    public function testGetRequestMethod(): void
    {
        $request = $this->createRequest($this->path, $this->options, $this->method);
        $this->assertEquals($this->method, $request->getRequestMethod());
    }

    public function testDefaultMethod(): void
    {
        $request = $this->createRequest($this->path, $this->options);
        $this->assertNull($request->getRequestMethod());
    }

    public function testToString(): void
    {
        $request = $this->createRequest($this->path, $this->options, $this->method);
        $expected = json_encode([
            '_className' => $request::class,
            'path' => $this->path,
            'method' => $this->method,
            'payload' => $this->options,
        ], JSON_UNESCAPED_SLASHES);

        $this->assertEquals($expected, (string) $request);
    }

    public function testGenerateLogData(): void
    {
        $request = $this->createRequest($this->path, $this->options, $this->method);
        $expected = [
            '_className' => $request::class,
            'path' => $this->path,
            'method' => $this->method,
            'payload' => $this->options,
        ];

        $this->assertEquals($expected, $request->generateLogData());
    }
}
