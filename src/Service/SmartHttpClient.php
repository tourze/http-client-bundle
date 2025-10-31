<?php

declare(strict_types=1);

namespace HttpClientBundle\Service;

use DateTimeImmutable;
use League\Uri\Uri;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\HttpClient\CurlHttpClient;
use Symfony\Component\HttpClient\NativeHttpClient;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;
use Tourze\Symfony\RuntimeContextBundle\Service\ContextServiceInterface;

/**
 * Symfony有自己的一套curl请求类库判断逻辑，默认是curl>amp>native
 * 因为我们部署环境不一定那么固定，所以自己覆盖了默认的http client，自己包装一层
 * HTTP Client 需要协程处理，防止共用了 curl 实例
 */
#[Autoconfigure(public: true)]
#[AutoconfigureTag(name: 'as-coroutine')]
#[AsAlias(id: HttpClientInterface::class)]
#[WithMonologChannel(channel: 'http_client')]
class SmartHttpClient implements HttpClientInterface
{
    private ?HttpClientInterface $inner = null;

    public function __construct(
        private readonly CacheInterface $cache,
        private readonly ContextServiceInterface $contextService,
        private readonly LoggerInterface $logger,
    ) {
    }

    private function getResolveCacheKey(string $host): string
    {
        return "api-client-resolve-{$host}";
    }

    public function refreshDomainResolveCache(string $host): string
    {
        // 我们在缓存中记录下当前的解析结果，方便我们下次不重复使用
        $cacheKey = $this->getResolveCacheKey($host);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($host) {
            $ip = $host;
            $validatedIp = filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4);
            if (false === $validatedIp) {
                $ip = gethostbyname($host);
            }
            $cacheTime = $_ENV["API_CLIENT_DNS_RESOLVE_{$host}_CACHE_TIME"] ?? $_ENV['API_CLIENT_DNS_RESOLVE_CACHE_TIME'] ?? 60 * 60;
            $item->expiresAfter(is_numeric($cacheTime) ? (int) $cacheTime : 3600);

            return $ip;
        });
    }

    protected function getInner(): HttpClientInterface
    {
        // 兼容不同的环境
        if ($this->contextService->supportCoroutine()) {
            $this->inner = new NativeHttpClient();
            $this->inner->setLogger($this->logger);
        }
        // 这里强制使用了CURL
        if (null === $this->inner) {
            $this->inner = new CurlHttpClient();
            $this->inner->setLogger($this->logger);
        }

        return $this->inner;
    }

    /** @phpstan-ignore-next-line missingType.iterableValue */
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        $startTime = new \DateTimeImmutable();
        $uri = Uri::new($url);

        $this->logger->debug('SmartHttpClient request started', [
            'method' => $method,
            'url' => $url,
            'host' => $uri->getHost(),
        ]);

        // 自定义DNS解析
        if (($_ENV['API_CLIENT_INTERNAL_DNS_RESOLVE'] ?? '') !== '' && !isset($options['resolve'])) {
            $resolvedIp = $this->refreshDomainResolveCache($uri->getHost() ?? '');
            if (!isset($options['resolve']) || !is_array($options['resolve'])) {
                $options['resolve'] = [];
            }
            $options['resolve'][(string) $uri->getHost()] = $resolvedIp;

            $this->logger->debug('SmartHttpClient DNS resolved', [
                'method' => $method,
                'url' => $url,
                'host' => $uri->getHost(),
                'resolvedIp' => $resolvedIp,
            ]);
        }
        if (($_ENV["API_CLIENT_DOMAIN_{$uri->getHost()}_DNS_RESOLVE"] ?? '') !== '') {
            $resolvedIp = $_ENV["API_CLIENT_DOMAIN_{$uri->getHost()}_DNS_RESOLVE"];
            if (!isset($options['resolve']) || !is_array($options['resolve'])) {
                $options['resolve'] = [];
            }
            $options['resolve'][(string) $uri->getHost()] = $resolvedIp;

            $this->logger->debug('SmartHttpClient using domain-specific DNS', [
                'method' => $method,
                'url' => $url,
                'host' => $uri->getHost(),
                'resolvedIp' => $resolvedIp,
            ]);
        }

        $inner = $this->getInner();
        $this->logger->debug('SmartHttpClient using inner client', [
            'method' => $method,
            'url' => $url,
            'innerClient' => get_class($inner),
        ]);

        try {
            $response = $inner->request($method, $url, $options);
            $endTime = new \DateTimeImmutable();
            $duration = ($endTime->getTimestamp() * 1000 + (int) $endTime->format('v')) - ($startTime->getTimestamp() * 1000 + (int) $startTime->format('v'));

                  // 使用 getInfo('http_code') 避免消费响应体
            $httpCode = $response->getInfo('http_code');
            $statusCode = intval(is_numeric($httpCode) ? $httpCode : 0);

            $this->logger->info('SmartHttpClient request completed', [
                'method' => $method,
                'url' => $url,
                'duration' => $duration / 1000,
                'statusCode' => $statusCode,
                'innerClient' => get_class($inner),
            ]);

            return $response;
        } catch (\Throwable $e) {
            $endTime = new \DateTimeImmutable();
            $duration = ($endTime->getTimestamp() * 1000 + (int) $endTime->format('v')) - ($startTime->getTimestamp() * 1000 + (int) $startTime->format('v'));

            $this->logger->error('SmartHttpClient request failed', [
                'method' => $method,
                'url' => $url,
                'duration' => $duration / 1000,
                'exception' => $e->getMessage(),
                'innerClient' => get_class($inner),
                'options' => $options,
            ]);

            throw $e;
        }
    }

    public function stream(iterable|ResponseInterface $responses, ?float $timeout = null): ResponseStreamInterface
    {
        return $this->getInner()->stream($responses, $timeout);
    }

    /** @phpstan-ignore-next-line missingType.iterableValue */
    public function withOptions(array $options): static
    {
        $clone = clone $this;
        $clone->inner = $this->getInner()->withOptions($options);

        return $clone;
    }

    /**
     * @internal For testing purposes only
     */
    public function getInnerClient(): ?HttpClientInterface
    {
        return $this->inner;
    }
}
