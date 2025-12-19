<?php

declare(strict_types=1);

namespace HttpClientBundle\Client;

use HttpClientBundle\Entity\HttpRequestLog;
use HttpClientBundle\Request\ApiRequest;
use HttpClientBundle\Request\AutoRetryRequest;
use HttpClientBundle\Request\CacheRequest;
use HttpClientBundle\Request\LockRequest;
use HttpClientBundle\Request\RequestInterface;
use HttpClientBundle\Service\HealthChecker;
use HttpClientBundle\Service\ProxyManager;
use HttpClientBundle\Service\RequestExecutor;
use HttpClientBundle\Service\RequestLogger;
use Laminas\Diagnostics\Check\CheckInterface;
use Laminas\Diagnostics\Result\ResultInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpClient\RetryableHttpClient;
use Symfony\Component\Lock\LockFactory;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Tourze\DoctrineAsyncInsertBundle\Service\AsyncInsertService;
use Tourze\Symfony\AopAsyncBundle\Attribute\Async;

/**
 * 通用的 API 客户端抽象基类
 *
 * 提供 HTTP 请求的通用功能，包括重试、缓存、锁等。
 * 子类需要实现具体的请求参数构建和响应解析逻辑。
 *
 * 注意：这是抽象类，不会被直接注册为服务。
 * 具体的子类应该自己配置 monolog channel 和其他服务属性。
 */
abstract class ApiClient implements CheckInterface
{
    private const DEFAULT_TIMEOUT_SECONDS = 10;

    private ?HealthChecker $healthChecker = null;

    private ?RequestLogger $requestLogger = null;

    private ?RequestExecutor $requestExecutor = null;

    abstract protected function getLogger(): LoggerInterface;

    abstract protected function getHttpClient(): HttpClientInterface;

    abstract protected function getLockFactory(): LockFactory;

    abstract protected function getCache(): CacheInterface;

    abstract protected function getEventDispatcher(): EventDispatcherInterface;

    abstract protected function getAsyncInsertService(): AsyncInsertService;

    /**
     * 组装要发起的请求地址
     */
    abstract protected function getRequestUrl(RequestInterface $request): string;

    /**
     * 获取要发起的请求方式
     */
    abstract protected function getRequestMethod(RequestInterface $request): string;

    /**
     * 组装要发送的数据
     * @return array<array-key, mixed>|null
     */
    abstract protected function getRequestOptions(RequestInterface $request): ?array;

    /**
     * 格式化，处理错误，返回数据
     */
    abstract protected function formatResponse(RequestInterface $request, ResponseInterface $response): mixed;

    /**
     * 所有客户端都应该实现这个，方便我们去做健康检查
     */
    public function getBaseUrl(): string
    {
        return '';
    }

    public function check(): ResultInterface
    {
        return $this->getHealthChecker()->check($this->getBaseUrl(), $this->getHttpClient());
    }

    public function getLabel(): string
    {
        return get_class($this);
    }

    /**
     * 根据请求的不同，我们封装不同的请求Client
     */
    private function detectHttpClient(RequestInterface $request): HttpClientInterface
    {
        $httpClient = $this->getHttpClient();
        $httpClient = $this->configureClientLogger($httpClient);
        $httpClient = $this->configureClientRetry($httpClient, $request);
        $httpClient = $this->wrapWithLockClient($httpClient);

        return $this->wrapWithCacheClient($httpClient);
    }

    private function configureClientLogger(HttpClientInterface $httpClient): HttpClientInterface
    {
        if ($httpClient instanceof LoggerAwareInterface) {
            $httpClient->setLogger(new NullLogger());
        }

        return $httpClient;
    }

    private function configureClientRetry(HttpClientInterface $httpClient, RequestInterface $request): HttpClientInterface
    {
        if ($request instanceof AutoRetryRequest) {
            $retries = $request->getMaxRetries();
            if ($retries > 0) {
                return new RetryableHttpClient($httpClient, maxRetries: $retries);
            }
        }

        return $httpClient;
    }

    private function wrapWithLockClient(HttpClientInterface $httpClient): HttpClientInterface
    {
        return new LockHttpClient($httpClient, $this->getLockFactory());
    }

    private function wrapWithCacheClient(HttpClientInterface $httpClient): HttpClientInterface
    {
        return new CacheHttpClient($httpClient, $this->getCache());
    }

    /**
     * 发起请求，并获得结果
     */
    public function request(RequestInterface $request): mixed
    {
        $method = $this->resolveRequestMethod($request);
        $url = $this->getRequestUrl($request);
        $options = $this->prepareRequestOptions($request);

        $log = $this->getRequestLogger()->initializeRequestLog($method, $url, $options, $request);

        return $this->executeRequestWithLogging($request, $method, $url, $options, $log);
    }

    private function resolveRequestMethod(RequestInterface $request): string
    {
        $method = $request->getRequestMethod();
        if (null === $method || '' === $method) {
            $method = $this->getRequestMethod($request);
        }

        return $method;
    }

    /**
     * @return array<array-key, mixed>
     */
    private function prepareRequestOptions(RequestInterface $request): array
    {
        $options = $this->getRequestOptions($request) ?? [];
        $options = $this->applyCacheOptions($options, $request);
        $options = $this->applyLockOptions($options, $request);

        return $this->applyTimeoutOptions($options);
    }

    /**
     * @param array<array-key, mixed> $options
     * @return array<array-key, mixed>
     */
    private function applyCacheOptions(array $options, RequestInterface $request): array
    {
        if ($request instanceof CacheRequest) {
            $cacheKey = $request->getCacheKey();
            $cacheTtl = $request->getCacheDuration();
            if ('' !== $cacheKey && $cacheTtl > 0) {
                $options['cache_key'] = $cacheKey;
                $options['cache_ttl'] = $cacheTtl;
            }
        }

        return $options;
    }

    /**
     * @param array<array-key, mixed> $options
     * @return array<array-key, mixed>
     */
    private function applyLockOptions(array $options, RequestInterface $request): array
    {
        if ($request instanceof LockRequest) {
            $options['lock_key'] = $request->getLockKey();
        }

        return $options;
    }

    /**
     * @param array<array-key, mixed> $options
     * @return array<array-key, mixed>
     */
    private function applyTimeoutOptions(array $options): array
    {
        if (!isset($options['timeout'])) {
            $timeout = $_ENV['HTTP_REQUEST_TIMEOUT'] ?? (string) self::DEFAULT_TIMEOUT_SECONDS;
            $options['timeout'] = intval(is_numeric($timeout) ? $timeout : self::DEFAULT_TIMEOUT_SECONDS);
        }

        return $options;
    }

    /**
     * @param array<array-key, mixed> $options
     */
    private function executeRequestWithLogging(RequestInterface $request, string $method, string $url, array $options, HttpRequestLog $log): mixed
    {
        $result = $exception = null;

        try {
            $result = $this->executeRequest($request, $method, $url, $options, $log);
        } catch (\Throwable $e) {
            $exception = $e;
        } finally {
            $this->getRequestLogger()->finalizeLogging($log, $exception);
        }

        if (null !== $exception) {
            throw $exception;
        }

        return $result;
    }

    /**
     * @param array<array-key, mixed> $options
     */
    private function executeRequest(RequestInterface $request, string $method, string $url, array $options, HttpRequestLog $log): mixed
    {
        $httpClient = $this->detectHttpClient($request);
        $requestResult = $this->getRequestExecutor()->sendRequest($httpClient, $method, $url, $options);
        $response = $requestResult['response'];
        $duration = $requestResult['duration'];
        $log->setStopwatchDuration((string) $duration);
        $result = $this->formatResponse($request, $response);
        $this->getRequestLogger()->updateLogWithResponse($log, $result);

        return $result;
    }

    /**
     * 发起异步请求，不关心响应结果
     */
    #[Async]
    public function asyncRequest(ApiRequest $request): void
    {
        try {
            $this->request($request);
        } catch (\Throwable $exception) {
            $this->getLogger()->error('发起异步请求时发生异常', [
                'request' => $request,
                'exception' => $exception,
            ]);
        }
    }

    /**
     * 我们有时候需要同步阻塞发起请求，但是不希望出现异常，就调用这个
     */
    public function silentRequest(ApiRequest $request): mixed
    {
        try {
            return $this->request($request);
        } catch (\Throwable $exception) {
            $this->getLogger()->error('发起静默请求时发生异常', [
                'request' => $request,
                'exception' => $exception,
            ]);
        }

        return null;
    }

    private function getHealthChecker(): HealthChecker
    {
        if (null === $this->healthChecker) {
            $this->healthChecker = new HealthChecker();
        }

        return $this->healthChecker;
    }

    private function getRequestLogger(): RequestLogger
    {
        if (null === $this->requestLogger) {
            $this->requestLogger = new RequestLogger(
                $this->getLogger(),
                $this->getAsyncInsertService()
            );
        }

        return $this->requestLogger;
    }

    private function getRequestExecutor(): RequestExecutor
    {
        if (null === $this->requestExecutor) {
            $proxyManager = new ProxyManager($this->getLogger());
            $this->requestExecutor = new RequestExecutor(
                $this->getEventDispatcher(),
                $proxyManager
            );
        }

        return $this->requestExecutor;
    }
}
