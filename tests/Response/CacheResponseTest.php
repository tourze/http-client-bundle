<?php

namespace HttpClientBundle\Tests\Response;

use HttpClientBundle\Response\CacheResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(CacheResponse::class)]
final class CacheResponseTest extends TestCase
{
    private CacheResponse $response;

    private int $statusCode = 200;

    /**
     * @var array<string, list<string>>
     */
    private array $headers = ['Content-Type' => ['application/json']];

    private string $content = '{"data": "test"}';

    /**
     * @var array<string, mixed>
     */
    private array $info = ['url' => 'https://example.com/api/test'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->response = new CacheResponse(
            $this->statusCode,
            $this->headers,
            $this->content,
            $this->info
        );
    }

    public function testGetStatusCode(): void
    {
        $this->assertEquals($this->statusCode, $this->response->getStatusCode());
    }

    public function testGetHeaders(): void
    {
        $this->assertEquals($this->headers, $this->response->getHeaders());
    }

    public function testGetContent(): void
    {
        $this->assertEquals($this->content, $this->response->getContent());
    }

    public function testGetInfo(): void
    {
        // 测试获取所有信息
        $this->assertEquals($this->info, $this->response->getInfo());

        // CacheResponse的getInfo方法总是返回整个info数组，即使指定了type参数
        // 这与标准HttpClient的行为不同，但是适合测试环境
        $this->assertEquals($this->info, $this->response->getInfo('url'));
    }

    public function testToArray(): void
    {
        $expected = [
            'status_code' => $this->statusCode,
            'headers' => $this->headers,
            'content' => $this->content,
            'info' => $this->info,
        ];

        $this->assertEquals($expected, $this->response->toArray());
    }

    public function testCancel(): void
    {
        // CacheResponse 的 cancel 方法应该不抛出异常，因为它是一个静态响应
        $this->expectNotToPerformAssertions();
        $this->response->cancel();
    }
}
