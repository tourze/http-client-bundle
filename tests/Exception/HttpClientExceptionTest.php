<?php

namespace HttpClientBundle\Tests\Exception;

use HttpClientBundle\Exception\HttpClientException;
use HttpClientBundle\Request\RequestInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @covers \HttpClientBundle\Exception\HttpClientException
 */
class HttpClientExceptionTest extends TestCase
{
    private RequestInterface|MockObject $request;
    private ResponseInterface|MockObject $response;
    private string $message = 'Test error message';
    private int $code = 500;

    protected function setUp(): void
    {
        /** @var RequestInterface&MockObject $request */
        $this->request = $this->createMock(RequestInterface::class);
        /** @var ResponseInterface&MockObject $response */
        $this->response = $this->createMock(ResponseInterface::class);

        $this->response->method('getContent')
            ->with(false)
            ->willReturn('{"error": "server error"}');

        $this->response->method('getInfo')
            ->willReturn(['url' => 'https://example.com/api']);
    }

    public function testConstructor(): void
    {
        $exception = new HttpClientException($this->request, $this->response, $this->message, $this->code);

        $this->assertEquals($this->message, $exception->getMessage());
        $this->assertEquals($this->code, $exception->getCode());
    }

    public function testGetContext(): void
    {
        $exception = new HttpClientException($this->request, $this->response, $this->message, $this->code);

        $expectedContext = [
            'content' => '{"error": "server error"}',
            'info' => ['url' => 'https://example.com/api'],
            'request' => $this->request,
        ];

        $this->assertEquals($expectedContext, $exception->getContext());
    }

    public function testSetContext(): void
    {
        $exception = new HttpClientException($this->request, $this->response, $this->message, $this->code);

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
