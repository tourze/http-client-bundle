<?php

declare(strict_types=1);

namespace HttpClientBundle\Client;

use HttpClientBundle\Exception\LockTimeoutHttpClientException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\Attribute\Exclude;
use Symfony\Component\Lock\LockFactory;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

/**
 * 带锁功能的 HTTP 客户端装饰器
 *
 * 这是一个工具类/装饰器，由 ApiClient 内部使用，不作为服务注册到容器。
 */
#[Exclude]
final class LockHttpClient implements HttpClientInterface
{
    public function __construct(
        private HttpClientInterface $client,
        private readonly LockFactory $lockFactory,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        $lockKey = $options['lock_key'] ?? null;
        unset($options['lock_key']);

        $this->logger->debug('LockHttpClient request started', [
            'method' => $method,
            'url' => $url,
            'lockKey' => $lockKey,
            'hasLock' => null !== $lockKey,
        ]);

        if (null === $lockKey) {
            $response = $this->client->request($method, $url, $options);

            $this->logger->debug('LockHttpClient request completed (no lock)', [
                'method' => $method,
                'url' => $url,
            ]);

            return $response;
        }

        $startTime = new \DateTimeImmutable();
        $lock = $this->lockFactory->createLock(is_string($lockKey) ? $lockKey : 'default');

        $this->logger->info('LockHttpClient attempting to acquire lock', [
            'method' => $method,
            'url' => $url,
            'lockKey' => $lockKey,
        ]);

        if (!$lock->acquire(true)) {
            $this->logger->warning('LockHttpClient failed to acquire lock', [
                'method' => $method,
                'url' => $url,
                'lockKey' => $lockKey,
            ]);

            throw new LockTimeoutHttpClientException('获取锁失败，请稍后重试');
        }

        $acquireEndTime = new \DateTimeImmutable();
        $acquireDuration = ($acquireEndTime->getTimestamp() * 1000 + (int) $acquireEndTime->format('v')) - ($startTime->getTimestamp() * 1000 + (int) $startTime->format('v'));
        $this->logger->info('LockHttpClient lock acquired', [
            'method' => $method,
            'url' => $url,
            'lockKey' => $lockKey,
            'acquireDuration' => $acquireDuration / 1000,
        ]);

        try {
            $requestStartTime = new \DateTimeImmutable();
            $response = $this->client->request($method, $url, $options);
            $requestEndTime = new \DateTimeImmutable();
            $requestDuration = ($requestEndTime->getTimestamp() * 1000 + (int) $requestEndTime->format('v')) - ($requestStartTime->getTimestamp() * 1000 + (int) $requestStartTime->format('v'));

            $this->logger->debug('LockHttpClient request completed with lock', [
                'method' => $method,
                'url' => $url,
                'lockKey' => $lockKey,
                'duration' => $requestDuration / 1000,
            ]);

            return $response;
        } finally {
            $releaseStartTime = new \DateTimeImmutable();
            try {
                $lock->release();
                $releaseEndTime = new \DateTimeImmutable();
                $releaseDuration = ($releaseEndTime->getTimestamp() * 1000 + (int) $releaseEndTime->format('v')) - ($releaseStartTime->getTimestamp() * 1000 + (int) $releaseStartTime->format('v'));

                $this->logger->debug('LockHttpClient lock released', [
                    'method' => $method,
                    'url' => $url,
                    'lockKey' => $lockKey,
                    'releaseDuration' => $releaseDuration / 1000,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('LockHttpClient failed to release lock', [
                    'method' => $method,
                    'url' => $url,
                    'lockKey' => $lockKey,
                    'exception' => $e->getMessage(),
                ]);
            } finally {
                $lock = null;
            }
        }
    }

    public function stream(iterable|ResponseInterface $responses, ?float $timeout = null): ResponseStreamInterface
    {
        return $this->client->stream($responses, $timeout);
    }

    public function withOptions(array $options): static
    {
        $clone = clone $this;
        $clone->client = $this->client->withOptions($options);

        return $clone;
    }
}
