<?php

namespace HttpClientBundle\Client;

use HttpClientBundle\Exception\UnsupportedOperationException;
use HttpClientBundle\Response\CacheResponse;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

#[When(env: 'never')]
class CacheHttpClient implements HttpClientInterface
{
    public function __construct(
        private HttpClientInterface $client,
        private readonly CacheInterface $cache,
    )
    {
    }

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        // 如果有声明缓存，则我们处理一次缓存
        $cacheKey = $options['cache_key'] ?? null;
        $cacheTTL = $options['cache_ttl'] ?? 60 * 60;
        unset($options['cache_key']);
        unset($options['cache_ttl']);

        if ($cacheKey === null) {
            return $this->client->request($method, $url, $options);
        }

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($method, $url, $options, $cacheTTL) {
            $realResponse = $this->client->request($method, $url, $options);
            $response = new CacheResponse(
                $realResponse->getStatusCode(),
                $realResponse->getHeaders(),
                $realResponse->getContent(),
                $realResponse->getInfo('debug'),
            );
            $item->expiresAfter($cacheTTL);
            $item->set($response);
            return $response;
        });
    }

    public function stream(iterable|ResponseInterface $responses, ?float $timeout = null): ResponseStreamInterface
    {
        throw new UnsupportedOperationException('Not implemented');
    }

    public function withOptions(array $options): static
    {
        $clone = clone $this;
        $clone->client = $this->client->withOptions($options);
        return $clone;
    }
}
