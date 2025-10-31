<?php

namespace HttpClientBundle\Tests\Exception;

use HttpClientBundle\Exception\HttpClientException;
use HttpClientBundle\Request\RequestInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(HttpClientException::class)]
final class HttpClientExceptionTest extends AbstractExceptionTestCase
{
    private RequestInterface $request;

    private ResponseInterface $response;

    private string $message = 'Test error message';

    private int $code = 500;

    protected function setUp(): void
    {
        parent::setUp();
        // RequestInterface 是简单的数据对象接口，使用匿名类
        $this->request = new class implements RequestInterface {
            public function getRequestPath(): string
            {
                return '/test';
            }

            public function getRequestOptions(): ?array
            {
                return null;
            }

            public function getRequestMethod(): string
            {
                return 'GET';
            }
        };

        $this->response = $this->createMock(ResponseInterface::class);

        $this->response->method('getContent')
            ->with(false)
            ->willReturn('{"error": "server error"}')
        ;

        $this->response->method('getInfo')
            ->willReturn(['url' => 'https://example.com/api'])
        ;
    }

    public function testConstructor(): void
    {
        // 创建抽象类的具体实现用于测试
        $exception = new class($this->request, $this->response, $this->message, $this->code) extends HttpClientException {
        };

        $this->assertEquals($this->message, $exception->getMessage());
        $this->assertEquals($this->code, $exception->getCode());
    }

    public function testGetContext(): void
    {
        // 创建抽象类的具体实现用于测试
        $exception = new class($this->request, $this->response, $this->message, $this->code) extends HttpClientException {
        };

        $expectedContext = [
            'content' => '{"error": "server error"}',
            'info' => ['url' => 'https://example.com/api'],
            'request' => $this->request,
        ];

        $this->assertEquals($expectedContext, $exception->getContext());
    }

    public function testSetContext(): void
    {
        // 创建抽象类的具体实现用于测试
        $exception = new class($this->request, $this->response, $this->message, $this->code) extends HttpClientException {
        };

        $newContext = ['test' => 'value'];
        $exception->setContext($newContext);

        $this->assertEquals($newContext, $exception->getContext());
    }

    public function testExtractResponse(): void
    {
        $extractedInfo = HttpClientException::extractResponse($this->response);

        $expected = [
            'content' => '{"error": "server error"}',
            'info' => ['url' => 'https://example.com/api'],
        ];

        $this->assertEquals($expected, $extractedInfo);
    }
}
