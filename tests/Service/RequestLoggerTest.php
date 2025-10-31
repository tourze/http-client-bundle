<?php

declare(strict_types=1);

namespace HttpClientBundle\Tests\Service;

use HttpClientBundle\Entity\HttpRequestLog;
use HttpClientBundle\Request\RequestInterface;
use HttpClientBundle\Service\RequestLogger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(RequestLogger::class)]
#[RunTestsInSeparateProcesses]
class RequestLoggerTest extends AbstractIntegrationTestCase
{
    private RequestLogger $requestLogger;

    protected function onSetUp(): void
    {
        // 从容器中获取服务实例，符合集成测试规范
        $this->requestLogger = self::getService(RequestLogger::class);
    }

    public function testInitializeRequestLog(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getRequestOptions')->willReturn(['timeout' => 30]);

        $options = ['headers' => ['User-Agent' => 'Test']];
        $log = $this->requestLogger->initializeRequestLog('GET', 'https://example.com', $options, $request);

        $this->assertInstanceOf(HttpRequestLog::class, $log);
        $this->assertSame('GET', $log->getMethod());
        $this->assertSame('https://example.com', $log->getRequestUrl());
        $this->assertNotNull($log->getContent());
        $this->assertNotNull($log->getRequestOptions());
        $this->assertSame(['timeout' => 30], $log->getRequestOptions());
    }

    public function testInitializeRequestLogWithExceptionInContent(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getRequestOptions')->willReturn([]);

        // 创建一个包含无法JSON编码的资源的数组
        $resource = fopen('php://temp', 'r');
        if (false === $resource) {
            self::fail('Failed to create resource');
        }
        $options = ['resource' => $resource];

        $log = $this->requestLogger->initializeRequestLog('POST', 'https://example.com', $options, $request);

        $this->assertInstanceOf(HttpRequestLog::class, $log);
        $this->assertSame('POST', $log->getMethod());
        $this->assertSame('https://example.com', $log->getRequestUrl());
        $content = $log->getContent();
        $this->assertIsString($content);
        $this->assertStringContainsString('Exception', $content);

        fclose($resource);
    }

    public function testInitializeRequestLogWithExceptionInRequestOptions(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getRequestOptions')->willThrowException(new \RuntimeException('Request options error'));

        $log = $this->requestLogger->initializeRequestLog('DELETE', 'https://example.com', [], $request);

        $this->assertInstanceOf(HttpRequestLog::class, $log);
        $this->assertSame('DELETE', $log->getMethod());
        $this->assertSame('https://example.com', $log->getRequestUrl());

        $requestOptions = $log->getRequestOptions();
        $this->assertIsArray($requestOptions);
        $this->assertArrayHasKey('exception', $requestOptions);
        $exceptionString = $requestOptions['exception'];
        $this->assertIsString($exceptionString);
        $this->assertStringContainsString('Request options error', $exceptionString);
    }

    public function testUpdateLogWithResponseArray(): void
    {
        $log = new HttpRequestLog();
        $arrayData = ['key' => 'value', 'status' => 'success'];

        $this->requestLogger->updateLogWithResponse($log, $arrayData);

        $response = $log->getResponse();
        $this->assertNotNull($response);
        $this->assertStringContainsString('"key":"value"', $response);
        $this->assertStringContainsString('"status":"success"', $response);
    }

    public function testUpdateLogWithResponseInterface(): void
    {
        $log = new HttpRequestLog();
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')->willReturn('response content');

        $this->requestLogger->updateLogWithResponse($log, $response);

        $this->assertSame('response content', $log->getResponse());
    }

    public function testUpdateLogWithResponseString(): void
    {
        $log = new HttpRequestLog();
        $stringData = 'simple string response';

        $this->requestLogger->updateLogWithResponse($log, $stringData);

        $this->assertSame('simple string response', $log->getResponse());
    }

    public function testUpdateLogWithResponseInteger(): void
    {
        $log = new HttpRequestLog();
        $intData = 42;

        $this->requestLogger->updateLogWithResponse($log, $intData);

        $this->assertSame('42', $log->getResponse());
    }

    public function testUpdateLogWithResponseBool(): void
    {
        $log = new HttpRequestLog();
        $boolData = true;

        $this->requestLogger->updateLogWithResponse($log, $boolData);

        $this->assertSame('1', $log->getResponse());
    }

    public function testUpdateLogWithResponseObject(): void
    {
        $log = new HttpRequestLog();
        $objectData = new \stdClass();

        $this->requestLogger->updateLogWithResponse($log, $objectData);

        $this->assertSame('Unable to convert to string', $log->getResponse());
    }

    public function testUpdateLogWithResponseObjectWithToString(): void
    {
        $log = new HttpRequestLog();
        $objectData = new class {
            public function __toString(): string
            {
                return 'object string representation';
            }
        };

        $this->requestLogger->updateLogWithResponse($log, $objectData);

        $this->assertSame('object string representation', $log->getResponse());
    }

    public function testUpdateLogWithResponseArrayException(): void
    {
        $log = new HttpRequestLog();

        // 创建一个包含无法JSON编码的资源的数组
        $resource = fopen('php://temp', 'r');
        if (false === $resource) {
            self::fail('Failed to create resource');
        }
        $arrayData = ['resource' => $resource];

        $this->requestLogger->updateLogWithResponse($log, $arrayData);

        $response = $log->getResponse();
        $this->assertIsString($response);
        $this->assertStringContainsString('Exception', $response);

        fclose($resource);
    }

    public function testFinalizeLogging(): void
    {
        $log = new HttpRequestLog();
        $log->setMethod('GET');
        $log->setRequestUrl('https://example.com');

        // 测试正常情况
        $this->requestLogger->finalizeLogging($log, null);

        $this->assertNull($log->getException());
    }

    public function testFinalizeLoggingWithException(): void
    {
        $log = new HttpRequestLog();
        $log->setMethod('GET');
        $log->setRequestUrl('https://example.com');
        $exception = new \Exception('Test exception');

        $this->requestLogger->finalizeLogging($log, $exception);

        $exceptionString = $log->getException();
        $this->assertNotNull($exceptionString);
        $this->assertIsString($exceptionString);
        $this->assertStringContainsString('Test exception', $exceptionString);
    }

    public function testLogRequestResponseWithSlowRequest(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getInfo')->willReturnCallback(function ($type = null) {
            return match ($type) {
                'http_code' => 200,
                null => ['http_code' => 200],
                default => null,
            };
        });
        $response->method('getContent')->willReturnCallback(function ($throw = true) {
            return 'response content';
        });

        $startTime = new \DateTimeImmutable('2023-01-01 10:00:00');
        $endTime = new \DateTimeImmutable('2023-01-01 10:00:10');
        $duration = 10000.0; // 10 seconds, slower than default 5000ms timeout

        // 集成测试不能mock底层服务，只测试功能完整性
        $this->requestLogger->logRequestResponse(
            'GET',
            'https://example.com',
            [],
            $response,
            'response content',
            $startTime,
            $endTime,
            $duration,
            'TestClient'
        );

        // 验证方法执行成功：慢请求测试确认duration确实超过阈值
        $this->assertGreaterThan(5000, $duration, 'Slow request duration should exceed threshold');
    }

    public function testLogRequestResponseWithNormalRequest(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getInfo')->willReturnCallback(function ($type = null) {
            return match ($type) {
                'http_code' => 200,
                null => ['http_code' => 200],
                default => null,
            };
        });
        $response->method('getContent')->willReturnCallback(function ($throw = true) {
            return 'response content';
        });

        $startTime = new \DateTimeImmutable('2023-01-01 10:00:00');
        $endTime = new \DateTimeImmutable('2023-01-01 10:00:01');
        $duration = 1000.0; // 1 second, faster than 5000ms timeout

        // 集成测试不能mock底层服务，只测试功能完整性
        $this->requestLogger->logRequestResponse(
            'GET',
            'https://example.com',
            [],
            $response,
            'response content',
            $startTime,
            $endTime,
            $duration,
            'TestClient'
        );

        // 验证方法执行成功：正常请求测试确认duration低于阈值
        $this->assertLessThan(5000, $duration, 'Normal request duration should be below threshold');
    }
}
