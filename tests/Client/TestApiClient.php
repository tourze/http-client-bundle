<?php

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

class TestApiClient extends ApiClient
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly HttpClientInterface $httpClient,
        private readonly LockFactory $lockFactory,
        private readonly CacheInterface $cache,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly AsyncInsertService $asyncInsertService,
    ) {
    }

    protected function getLockFactory(): LockFactory
    {
        return $this->lockFactory;
    }

    protected function getHttpClient(): HttpClientInterface
    {
        return $this->httpClient;
    }

    protected function getLogger(): LoggerInterface
    {
        return $this->logger;
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

    private string $baseUrl = '';

    protected function getRequestUrl(RequestInterface $request): string
    {
        return $this->getBaseUrl() . $request->getRequestPath();
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function setBaseUrl(string $baseUrl): void
    {
        $this->baseUrl = $baseUrl;
    }

    protected function getRequestMethod(RequestInterface $request): string
    {
        return $request->getRequestMethod() ?? 'GET';
    }

    /**
     * @return array<array-key, mixed>|null
     */
    protected function getRequestOptions(RequestInterface $request): ?array
    {
        return $request->getRequestOptions();
    }

    protected function formatResponse(RequestInterface $request, ResponseInterface $response): mixed
    {
        return json_decode($response->getContent(), true);
    }
}
