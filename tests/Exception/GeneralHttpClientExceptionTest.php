<?php

namespace HttpClientBundle\Tests\Exception;

use HttpClientBundle\Exception\GeneralHttpClientException;
use HttpClientBundle\Exception\HttpClientException;
use HttpClientBundle\Request\RequestInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(GeneralHttpClientException::class)]
final class GeneralHttpClientExceptionTest extends AbstractExceptionTestCase
{
    private RequestInterface $request;

    private ResponseInterface $response;

    private string $message = 'General HTTP client error';

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
            ->willReturn('{"error": "general error"}')
        ;

        $this->response->method('getInfo')
            ->willReturn(['url' => 'https://example.com/api'])
        ;
    }

    public function testConstructor(): void
    {
        $exception = new GeneralHttpClientException($this->request, $this->response, $this->message, $this->code);

        $this->assertEquals($this->message, $exception->getMessage());
        $this->assertEquals($this->code, $exception->getCode());
    }

    public function testGetContext(): void
    {
        $exception = new GeneralHttpClientException($this->request, $this->response, $this->message, $this->code);

        $expectedContext = [
            'content' => '{"error": "general error"}',
            'info' => ['url' => 'https://example.com/api'],
            'request' => $this->request,
        ];

        $this->assertEquals($expectedContext, $exception->getContext());
    }

    public function testSetContext(): void
    {
        $exception = new GeneralHttpClientException($this->request, $this->response, $this->message, $this->code);

        $newContext = ['test' => 'value'];
        $exception->setContext($newContext);

        $this->assertEquals($newContext, $exception->getContext());
    }

    public function testInheritsFromHttpClientException(): void
    {
        $exception = new GeneralHttpClientException($this->request, $this->response, $this->message, $this->code);

        $this->assertInstanceOf(HttpClientException::class, $exception);
    }
}
