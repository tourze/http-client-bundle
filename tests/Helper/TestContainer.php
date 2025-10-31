<?php

declare(strict_types=1);

namespace HttpClientBundle\Tests\Helper;

use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tourze\DoctrineAsyncInsertBundle\Service\AsyncInsertService as DoctrineService;

/**
 * 测试用的Container实现
 */
class TestContainer implements ContainerInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private CacheInterface $cache,
        private EventDispatcherInterface $eventDispatcher,
        private LockFactory $lockFactory,
        private DoctrineService $doctrineService,
    ) {
    }

    public function get(string $id): mixed
    {
        return match ($id) {
            'HttpClientBundle\Client\ApiClient::getHttpClient',
            'HttpClientBundle\Tests\Client\TestApiClient::getHttpClient' => $this->httpClient,
            'HttpClientBundle\Client\ApiClient::getCache',
            'HttpClientBundle\Tests\Client\TestApiClient::getCache' => $this->cache,
            'HttpClientBundle\Client\ApiClient::getEventDispatcher',
            'HttpClientBundle\Tests\Client\TestApiClient::getEventDispatcher' => $this->eventDispatcher,
            'HttpClientBundle\Client\ApiClient::getLockFactory',
            'HttpClientBundle\Tests\Client\TestApiClient::getLockFactory' => $this->lockFactory,
            'HttpClientBundle\Client\ApiClient::getDoctrineService',
            'HttpClientBundle\Tests\Client\TestApiClient::getDoctrineService' => $this->doctrineService,
            default => null,
        };
    }

    public function has(string $id): bool
    {
        return !is_null($this->get($id));
    }
}
