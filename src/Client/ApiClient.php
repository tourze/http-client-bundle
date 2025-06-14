<?php

namespace HttpClientBundle\Client;

use Carbon\Carbon;
use HttpClientBundle\Entity\HttpRequestLog;
use HttpClientBundle\Event\RequestEvent;
use HttpClientBundle\Event\ResponseEvent;
use HttpClientBundle\Exception\HttpClientException;
use HttpClientBundle\Request\ApiRequest;
use HttpClientBundle\Request\AutoRetryRequest;
use HttpClientBundle\Request\CacheRequest;
use HttpClientBundle\Request\LockRequest;
use HttpClientBundle\Request\RequestInterface;
use HttpClientBundle\Service\SmartHttpClient;
use Laminas\Diagnostics\Check\CheckInterface;
use Laminas\Diagnostics\Result\Failure;
use Laminas\Diagnostics\Result\ResultInterface;
use Laminas\Diagnostics\Result\Skip;
use Laminas\Diagnostics\Result\Success;
use Laminas\Diagnostics\Result\Warning;
use League\Uri\Uri;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Spatie\SslCertificate\SslCertificate;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpClient\Exception\TimeoutException;
use Symfony\Component\HttpClient\RetryableHttpClient;
use Symfony\Component\Lock\LockFactory;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Contracts\Service\Attribute\SubscribedService;
use Symfony\Contracts\Service\ServiceMethodsSubscriberTrait;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Tourze\BacktraceHelper\Backtrace;
use Tourze\BacktraceHelper\ExceptionPrinter;
use Tourze\DoctrineAsyncInsertBundle\Service\AsyncInsertService as DoctrineService;
use Tourze\Symfony\Async\Attribute\Async;
use Yiisoft\Json\Json;

/**
 * 通用的客户端实现
 */
#[AutoconfigureTag('monolog.logger', ['channel' => 'api_client'])]
abstract class ApiClient implements CheckInterface, ServiceSubscriberInterface
{
    use ServiceMethodsSubscriberTrait;

    #[SubscribedService]
    private function getHttpClient(): SmartHttpClient
    {
        return $this->container->get(__METHOD__);
    }

    #[Required]
    public ?LoggerInterface $apiClientLogger;

    #[SubscribedService]
    private function getLockFactory(): LockFactory
    {
        return $this->container->get(__METHOD__);
    }

    #[SubscribedService]
    private function getCache(): CacheInterface
    {
        return $this->container->get(__METHOD__);
    }

    #[SubscribedService]
    private function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->container->get(__METHOD__);
    }

    #[SubscribedService]
    private function getDoctrineService(): DoctrineService
    {
        return $this->container->get(__METHOD__);
    }

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

    private function isPortOpen($ip, $port, int $timeout = 5): bool
    {
        $connection = @fsockopen($ip, $port, $errno, $errstr, $timeout);

        if (is_resource($connection)) {
            // 端口是开放的
            fclose($connection); // 关闭连接

            return true;
        }

        // 端口是关闭的或不可访问
        return false;
    }

    public function check(): ResultInterface
    {
        $baseUrl = $this->getBaseUrl();
        if (empty($baseUrl)) {
            return new Skip('未实现getBaseUrl，不处理');
        }

        $uri = Uri::new($baseUrl);
        $host = $uri->getHost();
        $port = $uri->getPort() ?: ('https' === $uri->getScheme() ? 443 : 80); // 基本都是web服务

        $ip = $this->getHttpClient()->refreshDomainResolveCache($host);
        if ($ip === $host) {
            return new Failure("{$host}:解析DNS失败");
        }

        if (!$this->isPortOpen($host, $port, 3)) {
            return new Failure("{$host}({$ip}):{$port}端口不通");
        }

        $certificate = SslCertificate::createForHostName($host);
        if (!$certificate->isValid()) {
            return new Failure("域名SSL证书已过期[$host]");
        }
        // 提前7天提醒
        if ($certificate->expirationDate()->diffInDays(Carbon::now()) <= 7) {
            return new Warning("域名SSL证书过期提醒[$host]将于[{$certificate->expirationDate()->format('Y-m-d H:i:s')}]过期");
        }

        return new Success("{$host}({$ip}):{$port}端口连接成功");
    }

    public function getLabel(): string
    {
        return static::class;
    }

    /**
     * 根据请求的不同，我们封装不同的请求Client
     */
    private function detectHttpClient(RequestInterface $request): HttpClientInterface
    {
        $httpClient = $this->getHttpClient();

        // 因为我们有自己的日志，所以不使用内置的
        if ($httpClient instanceof LoggerAwareInterface) {
            $httpClient->setLogger(new NullLogger());
        }

        // 重试请求
        if ($request instanceof AutoRetryRequest) {
            $c = $request->getMaxRetries();
            if ($c > 0) {
                $httpClient = new RetryableHttpClient($httpClient, maxRetries: $c);
            }
        }

        $httpClient = new LockHttpClient($httpClient, $this->getLockFactory());
        return new CacheHttpClient($httpClient, $this->getCache());
    }

    /**
     * 发起请求，并获得结果
     */
    public function request(RequestInterface $request): mixed
    {
        $method = $request->getRequestMethod();
        if (!$method) {
            $method = $this->getRequestMethod($request);
        }

        $url = $this->getRequestUrl($request);

        $options = $this->getRequestOptions($request);

        // 缓存的处理
        if ($request instanceof CacheRequest) {
            $options['cache_key'] = $request->getCacheKey();
            $options['cache_ttl'] = $request->getCacheDuration();
        }

        // 并发锁的处理
        if ($request instanceof LockRequest) {
            $options['lock_key'] = $request->getLockKey();
        }

        // 默认加个超时时间
        if (!isset($options['timeout'])) {
            $options['timeout'] = intval($_ENV['HTTP_REQUEST_TIMEOUT'] ?? 10);
        }

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

        $result = $exception = null;
        $duration = 0;
        try {
            $httpClient = $this->detectHttpClient($request);
            $response = $this->sendRequest($httpClient, $method, $url, $options, $duration);
            $log->setStopwatchDuration($duration);
            $result = $this->formatResponse($request, $response);
            if (is_array($result)) {
                try {
                    $log->setResponse(Json::encode($result));
                } catch (\Throwable $exception) {
                    $log->setResponse(ExceptionPrinter::exception($exception));
                }
            } elseif ($result instanceof ResponseInterface) {
                $log->setResponse($result->getContent());
            }
        } catch (\Throwable $e) {
            $exception = $e;
        } finally {
            if (null !== $exception) {
                $log->setException(ExceptionPrinter::exception($exception));
            }

            try {
                $this->processLogModel($log);
                $this->getDoctrineService()->asyncInsert($log);
            } catch (\Throwable $e) {
                $this->apiClientLogger?->error('记录请求日志时发生异常', [
                    'exception' => $e,
                ]);
            }
        }

        if (null !== $exception) {
            throw $exception;
        }

        return $result;
    }

    /**
     * 存库的数据做一些额外格式化
     */
    private function processLogModel(HttpRequestLog $log): void
    {
        // options可能带有一些资源喔
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

    /**
     * 发起请求并打印日志
     *
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    protected function sendRequest(HttpClientInterface $client, string $method, string $url, array $options, &$duration = 0): ResponseInterface
    {
        $event = new RequestEvent();
        $event->setMethod($method);
        $event->setUrl($url);
        $event->setOptions($options);
        $this->getEventDispatcher()->dispatch($event);

        $startTime = Carbon::now();

        // 增加代理域名判断
        $proxyDomains = $_ENV['HTTP_REQUEST_PROXY_DOMAINS'] ?? '';
        $proxyDSN = $_ENV['HTTP_REQUEST_PROXY'] ?? '';
        if ($proxyDomains && $proxyDSN) {
            if (!is_array($proxyDomains)) {
                $proxyDomains = explode(',', $proxyDomains);
            }
            foreach ($proxyDomains as $proxyDomain) {
                if (str_contains($url, $proxyDomain)) {
                    $options['proxy'] = $_ENV['HTTP_REQUEST_PROXY'];
                    $this->apiClientLogger?->debug("当前HTTP代理:{$options['proxy']}");
                    break;
                }
            }
        }

        // 在这里，我们尝试下读取缓存中的DNS解析结果

        $response = $client->request($method, $url, $options);

        $headers = $response->getInfo('response_headers');
        if (is_string($headers) && str_starts_with($headers, 'Due to a bug in curl')) {
            $headers = '';
        }

        // 这里可能会发生超时，我们先catch这个超时异常，在保存日志后再抛出来
        $timeoutException = null;
        try {
            $content = $response->getContent(false);
        } catch (TimeoutException $exception) {
            $content = ExceptionPrinter::exception($exception);
            $timeoutException = $exception;
        }

        // 要注意，这里截取结束时间要放到 getContent 之后
        $endTime = Carbon::now();
        $duration = round($endTime->getPreciseTimestamp() / 1000 - $startTime->getPreciseTimestamp() / 1000, 6);
        $statusCode = intval($response->getInfo('http_code'));

        $maxTime = $_ENV['HTTP_REQUEST_ERROR_TIMEOUT'] ?? 5000;
        if ($duration > $maxTime) {
            // 大于5s，基本就是有问题的了
            $this->apiClientLogger?->error(sprintf('请求外部接口时可能发生超时[%s]', static::class), [
                'startTime' => $startTime->format('Y-m-d H:i:s.u'),
                'endTime' => $endTime->format('Y-m-d H:i:s.u'),
                'duration' => $duration,
                'method' => $method,
                'url' => $url,
                'options' => $options,
                'response' => HttpClientException::extractResponse($response),
                'backtrace' => Backtrace::create()->toString(),
            ]);
        } else {
            $this->apiClientLogger?->info(sprintf('获取外部API响应结果[%s]', static::class), [
                'startTime' => $startTime->format('Y-m-d H:i:s.u'),
                'endTime' => $endTime->format('Y-m-d H:i:s.u'),
                'duration' => $duration,
                'method' => $method,
                'url' => $url,
                'options' => $options,
                'statusCode' => $statusCode,
                'responseHeaders' => $headers,
                'content' => $content,
                'backtrace' => Backtrace::create()->toString(),
            ]);
        }

        $event = new ResponseEvent();
        $event->setMethod($method);
        $event->setUrl($url);
        $event->setOptions($options);
        $event->setDuration($duration);
        $event->setStatusCode($statusCode);
        $this->getEventDispatcher()->dispatch($event);

        if (null !== $timeoutException) {
            throw $timeoutException;
        }

        return $response;
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
            $this->apiClientLogger?->error('发起异步请求时发生异常', [
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
            $this->apiClientLogger?->error('发起静默请求时发生异常', [
                'request' => $request,
                'exception' => $exception,
            ]);
        }
        return null;
    }
}
