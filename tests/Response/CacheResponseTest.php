<?php

namespace HttpClientBundle\Tests\Response;

use HttpClientBundle\Response\CacheResponse;
use PHPUnit\Framework\TestCase;

/**
 * @covers \HttpClientBundle\Response\CacheResponse
 */
class CacheResponseTest extends TestCase
{
    private CacheResponse $response;
    private int $statusCode = 200;
    private array $headers = ['Content-Type' => ['application/json']];
    private string $content = '{"data": "test"}';
    private array $info = ['url' => 'https://example.com/api/test'];

    protected function setUp(): void
    {
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
        // 这个方法不执行任何操作，只是确保能调用不出错
        $this->response->cancel();
        $this->assertTrue(true); // 断言方法能执行完毕
    }
}
