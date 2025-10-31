<?php

declare(strict_types=1);

namespace HttpClientBundle\Service;

use DateTimeImmutable;
use HttpClientBundle\Event\RequestEvent;
use HttpClientBundle\Event\ResponseEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Tourze\BacktraceHelper\ExceptionPrinter;

/**
 * 请求执行服务
 */
class RequestExecutor
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ProxyManager $proxyManager,
    ) {
    }

    /**
     * @param array<array-key, mixed> $options
     * @return array{response: ResponseInterface, duration: float}
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function sendRequest(HttpClientInterface $client, string $method, string $url, array $options): array
    {
        $this->dispatchRequestEvent($method, $url, $options);

        $startTime = new \DateTimeImmutable();
        $options = $this->proxyManager->applyProxySettings($url, $options);

        $response = $client->request($method, $url, $options);
        $endTime = new \DateTimeImmutable();
        $duration = $this->calculateDuration($startTime, $endTime);

        // 避免在装饰器链中消费响应，延迟到业务逻辑中处理
        $this->dispatchResponseEvent($method, $url, $options, $duration, $response);

        return ['response' => $response, 'duration' => $duration];
    }

    /**
     * @param array<array-key, mixed> $options
     */
    private function dispatchRequestEvent(string $method, string $url, array $options): void
    {
        $event = new RequestEvent();
        $event->setMethod($method);
        $event->setUrl($url);
        $event->setOptions($options);
        $this->eventDispatcher->dispatch($event);
    }

    /**
     * @param array<array-key, mixed> $options
     */
    private function dispatchResponseEvent(string $method, string $url, array $options, float $duration, ResponseInterface $response): void
    {
        // 延迟获取状态码，避免在装饰器链中消费响应
        $statusCode = 0;
        try {
            $httpCode = $response->getInfo('http_code');
            $statusCode = intval(is_numeric($httpCode) ? $httpCode : 0);
        } catch (\Throwable $e) {
            // 如果获取状态码失败，使用默认值0，避免消费响应
            $statusCode = 0;
        }

        $event = new ResponseEvent();
        $event->setMethod($method);
        $event->setUrl($url);
        $event->setOptions($options);
        $event->setDuration($duration);
        $event->setStatusCode($statusCode);
        $this->eventDispatcher->dispatch($event);
    }

    
    private function calculateDuration(\DateTimeImmutable $startTime, \DateTimeImmutable $endTime): float
    {
        $startMs = $startTime->getTimestamp() * 1000 + (int) $startTime->format('v');
        $endMs = $endTime->getTimestamp() * 1000 + (int) $endTime->format('v');

        return round(($endMs - $startMs) / 1000, 6);
    }
}
