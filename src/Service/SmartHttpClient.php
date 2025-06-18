<?php

namespace HttpClientBundle\Service;

use League\Uri\Uri;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
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
#[AutoconfigureTag('as-coroutine')]
#[AsAlias(HttpClientInterface::class)]
class SmartHttpClient implements HttpClientInterface
{
    private ?HttpClientInterface $inner = null;

    public function __construct(
        private readonly CacheInterface $cache,
        private readonly ContextServiceInterface $contextService,
    )
    {
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
            if (!filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4)) {
                $ip = gethostbyname($host);
            }
            $item->expiresAfter($_ENV["API_CLIENT_DNS_RESOLVE_{$host}_CACHE_TIME"] ?? $_ENV['API_CLIENT_DNS_RESOLVE_CACHE_TIME'] ?? 60 * 60);
            return $ip;
        });
    }

    protected function getInner(): HttpClientInterface
    {
        // 兼容不同的环境
        if ($this->contextService->supportCoroutine()) {
            $this->inner = new NativeHttpClient();
        }
        // 这里强制使用了CURL
        if ($this->inner === null) {
            $this->inner = new CurlHttpClient();
        }
        return $this->inner;
    }

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        $uri = Uri::new($url);
        // 自定义DNS解析
        if (!empty($_ENV['API_CLIENT_INTERNAL_DNS_RESOLVE']) && !isset($options['resolve'])) {
            $options['resolve'][$uri->getHost()] = $this->refreshDomainResolveCache($uri->getHost());
        }
        if (!empty($_ENV["API_CLIENT_DOMAIN_{$uri->getHost()}_DNS_RESOLVE"])) {
            $options['resolve'][$uri->getHost()] = $_ENV["API_CLIENT_DOMAIN_{$uri->getHost()}_DNS_RESOLVE"];
        }

        return $this->getInner()->request($method, $url, $options);
    }

    public function stream(iterable|ResponseInterface $responses, ?float $timeout = null): ResponseStreamInterface
    {
        return $this->getInner()->stream($responses, $timeout);
    }

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
