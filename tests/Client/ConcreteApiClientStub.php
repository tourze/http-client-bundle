<?php

declare(strict_types=1);

namespace HttpClientBundle\Tests\Client;

use HttpClientBundle\Client\ApiClient;
use HttpClientBundle\Request\RequestInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Tourze\DoctrineAsyncInsertBundle\Service\AsyncInsertService;

/**
 * ApiClient 的具体实现，专门用于测试
 *
 * 由于 ApiClient 是抽象类，测试需要一个具体实现。
 * 这个类提供了测试所需的最小实现。
 */
final class ConcreteApiClientStub extends ApiClient
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly HttpClientInterface $httpClient,
        private readonly LockFactory $lockFactory,
        private readonly CacheInterface $cache,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly AsyncInsertService $asyncInsertService,
        private string &$baseUrl,
    ) {
    }

    protected function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    protected function getHttpClient(): HttpClientInterface
    {
        return $this->httpClient;
    }

    protected function getLockFactory(): LockFactory
    {
        return $this->lockFactory;
    }

    protected function getCache(): CacheInterface
    {
        return $this->cache;
    }

    protected function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    protected function getAsyncInsertService(): AsyncInsertService
    {
        return $this->asyncInsertService;
    }

    protected function getRequestUrl(RequestInterface $request): string
    {
        return $this->baseUrl . '/test';
    }

    protected function getRequestMethod(RequestInterface $request): string
    {
        return 'GET';
    }

    protected function getRequestOptions(RequestInterface $request): ?array
    {
        return [];
    }

    protected function formatResponse(RequestInterface $request, ResponseInterface $response): mixed
    {
        return $response->toArray();
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }
}
