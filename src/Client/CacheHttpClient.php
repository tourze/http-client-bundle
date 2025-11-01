<?php

declare(strict_types=1);

namespace HttpClientBundle\Client;

use DateTimeImmutable;
use HttpClientBundle\Response\CacheResponse;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

#[WithMonologChannel(channel: 'http_client')]
class CacheHttpClient implements HttpClientInterface
{
    public function __construct(
        private HttpClientInterface $client,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        // 如果有声明缓存，则我们处理一次缓存
        $cacheKey = $options['cache_key'] ?? null;
        $cacheTTL = $options['cache_ttl'] ?? 60 * 60;
        unset($options['cache_key'], $options['cache_ttl']);

        $startTime = new \DateTimeImmutable();
        $this->logger->debug('CacheHttpClient request started', [
            'method' => $method,
            'url' => $url,
            'cacheKey' => $cacheKey,
            'cacheTTL' => $cacheTTL,
            'hasCache' => null !== $cacheKey,
        ]);

        if (null === $cacheKey) {
            $response = $this->client->request($method, $url, $options);
            $endTime = new \DateTimeImmutable();
            $duration = ($endTime->getTimestamp() * 1000 + (int) $endTime->format('v')) - ($startTime->getTimestamp() * 1000 + (int) $startTime->format('v'));

            $this->logger->debug('CacheHttpClient request completed (no cache)', [
                'method' => $method,
                'url' => $url,
                'duration' => $duration / 1000,
            ]);

            return $response;
        }

        // 临时禁用缓存功能，避免在装饰器链中消费响应
        // TODO: 重新设计缓存机制，使其不干扰AsyncResponse
        $this->logger->warning('CacheHttpClient is temporarily disabled to avoid response consumption', [
            'method' => $method,
            'url' => $url,
            'cacheKey' => $cacheKey,
        ]);

        return $this->client->request($method, $url, $options);
    }

    public function stream(iterable|ResponseInterface $responses, ?float $timeout = null): ResponseStreamInterface
    {
        return $this->client->stream($responses, $timeout);
    }

    public function getCache(): CacheInterface
    {
        return $this->cache;
    }

    public function withOptions(array $options): static
    {
        $clone = clone $this;
        $clone->client = $this->client->withOptions($options);

        return $clone;
    }
}
