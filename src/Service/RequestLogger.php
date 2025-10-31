<?php

declare(strict_types=1);

namespace HttpClientBundle\Service;

use DateTimeImmutable;
use HttpClientBundle\Entity\HttpRequestLog;
use HttpClientBundle\Exception\HttpClientException;
use HttpClientBundle\Request\RequestInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Tourze\BacktraceHelper\Backtrace;
use Tourze\BacktraceHelper\ExceptionPrinter;
use Tourze\DoctrineAsyncInsertBundle\Service\AsyncInsertService;
use Yiisoft\Json\Json;

/**
 * 请求日志记录服务
 */
#[WithMonologChannel(channel: 'http_client')]
#[Autoconfigure(public: true)]
class RequestLogger
{
    private const ERROR_TIMEOUT_MS = 5000;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly AsyncInsertService $asyncInsertService,
    ) {
    }

    /**
     * @param array<array-key, mixed> $options
     */
    public function initializeRequestLog(string $method, string $url, array $options, RequestInterface $request): HttpRequestLog
    {
        $log = new HttpRequestLog();
        $log->setMethod($method);
        $log->setRequestUrl($url);

        try {
            $log->setContent(Json::encode($options));
        } catch (\Throwable $exception) {
            $log->setContent(ExceptionPrinter::exception($exception));
        }

        try {
            $log->setRequestOptions($request->getRequestOptions());
        } catch (\Throwable $exception) {
            $log->setRequestOptions([
                'exception' => ExceptionPrinter::exception($exception),
            ]);
        }

        return $log;
    }

    public function updateLogWithResponse(HttpRequestLog $log, mixed $result): void
    {
        if (is_array($result)) {
            try {
                $log->setResponse(Json::encode($result));
            } catch (\Throwable $exception) {
                $log->setResponse(ExceptionPrinter::exception($exception));
            }
        } elseif ($result instanceof ResponseInterface) {
            $log->setResponse($result->getContent());
        } else {
            $log->setResponse(is_scalar($result) || (is_object($result) && method_exists($result, '__toString'))
                ? (string) $result
                : 'Unable to convert to string');
        }
    }

    public function finalizeLogging(HttpRequestLog $log, ?\Throwable $exception): void
    {
        if (null !== $exception) {
            $log->setException(ExceptionPrinter::exception($exception));
        }

        try {
            $this->processLogModel($log);
            $this->asyncInsertService->asyncInsert($log);
        } catch (\Throwable $e) {
            $this->logger->error('记录请求日志时发生异常', [
                'exception' => $e,
            ]);
        }
    }

    /**
     * @param array<string, mixed> $options
     */
    public function logRequestResponse(
        string $method,
        string $url,
        array $options,
        ResponseInterface $response,
        string $content,
        \DateTimeImmutable $startTime,
        \DateTimeImmutable $endTime,
        float $duration,
        string $clientClass,
    ): void {
        $httpCode = $response->getInfo('http_code');
        $statusCode = intval(is_numeric($httpCode) ? $httpCode : 0);
        $envTimeout = $_ENV['HTTP_REQUEST_ERROR_TIMEOUT'] ?? self::ERROR_TIMEOUT_MS;
        $maxTime = is_numeric($envTimeout) ? (int) $envTimeout : self::ERROR_TIMEOUT_MS;

        if ($duration > $maxTime) {
            $this->logSlowRequest($method, $url, $options, $response, $startTime, $endTime, $duration, $clientClass);
        } else {
            $this->logNormalRequest($method, $url, $options, $statusCode, $content, $startTime, $endTime, $duration, $clientClass);
        }
    }

    /**
     * @param array<string, mixed> $options
     */
    private function logSlowRequest(
        string $method,
        string $url,
        array $options,
        ResponseInterface $response,
        \DateTimeImmutable $startTime,
        \DateTimeImmutable $endTime,
        float $duration,
        string $clientClass,
    ): void {
        $this->logger->error(sprintf('请求外部接口时可能发生超时[%s]', $clientClass), [
            'startTime' => $startTime->format('Y-m-d H:i:s.u'),
            'endTime' => $endTime->format('Y-m-d H:i:s.u'),
            'duration' => $duration,
            'method' => $method,
            'url' => $url,
            'options' => $options,
            'response' => HttpClientException::extractResponse($response),
            'backtrace' => Backtrace::create()->toString(),
        ]);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function logNormalRequest(
        string $method,
        string $url,
        array $options,
        int $statusCode,
        string $content,
        \DateTimeImmutable $startTime,
        \DateTimeImmutable $endTime,
        float $duration,
        string $clientClass,
    ): void {
        $this->logger->info(sprintf('获取外部API响应结果[%s]', $clientClass), [
            'startTime' => $startTime->format('Y-m-d H:i:s.u'),
            'endTime' => $endTime->format('Y-m-d H:i:s.u'),
            'duration' => $duration,
            'method' => $method,
            'url' => $url,
            'options' => $options,
            'statusCode' => $statusCode,
            'responseHeaders' => '',
            'content' => $content,
            'backtrace' => Backtrace::create()->toString(),
        ]);
    }

    private function processLogModel(HttpRequestLog $log): void
    {
        $options = $log->getRequestOptions();
        $body = $options['body'] ?? null;
        if (is_array($body)) {
            foreach ($body as $k => $v) {
                if (is_resource($v)) {
                    $body[$k] = get_resource_type($v);
                }
            }
            $options['body'] = $body;
        }

        $log->setRequestOptions($options);
    }
}
