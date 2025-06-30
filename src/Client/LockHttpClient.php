<?php

namespace HttpClientBundle\Client;

use HttpClientBundle\Exception\LockTimeoutHttpClientException;
use HttpClientBundle\Exception\UnsupportedOperationException;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\Lock\LockFactory;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

#[When(env: 'never')]
class LockHttpClient implements HttpClientInterface
{
    public function __construct(
        private HttpClientInterface $client,
        private readonly LockFactory $lockFactory,
    )
    {
    }

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        $lockKey = $options['lock_key'] ?? null;
        unset($options['lock_key']);

        if ($lockKey === null) {
            return $this->client->request($method, $url, $options);
        }

        $lock = $this->lockFactory->createLock($lockKey);
        if (!$lock->acquire(true)) {
            throw new LockTimeoutHttpClientException('获取锁失败，请稍后重试');
        }

        try {
            return $this->client->request($method, $url, $options);
        } finally {
            try {
                $lock->release();
            } finally {
                $lock = null;
            }
        }
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
