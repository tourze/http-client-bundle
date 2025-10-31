<?php

declare(strict_types=1);

namespace HttpClientBundle\Service;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * 代理管理服务
 */
#[WithMonologChannel(channel: 'http_client')]
#[Autoconfigure(public: true)]
readonly class ProxyManager
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param array<array-key, mixed> $options
     * @return array<array-key, mixed>
     */
    public function applyProxySettings(string $url, array $options): array
    {
        $proxyDomains = $this->getProxyDomains();
        $proxyDSN = $_ENV['HTTP_REQUEST_PROXY'] ?? '';

        if (0 === count($proxyDomains) || '' === $proxyDSN) {
            return $options;
        }

        return $this->addProxyIfUrlMatches($url, $options, $proxyDomains);
    }

    /**
     * @return array<string>
     */
    private function getProxyDomains(): array
    {
        $proxyDomains = $_ENV['HTTP_REQUEST_PROXY_DOMAINS'] ?? '';

        if ('' === $proxyDomains) {
            return [];
        }

        if (is_array($proxyDomains)) {
            return array_filter($proxyDomains, 'is_string');
        }

        return explode(',', is_string($proxyDomains) ? $proxyDomains : '');
    }

    /**
     * @param array<array-key, mixed> $options
     * @param array<string> $proxyDomains
     * @return array<array-key, mixed>
     */
    private function addProxyIfUrlMatches(string $url, array $options, array $proxyDomains): array
    {
        foreach ($proxyDomains as $proxyDomain) {
            if (str_contains($url, $proxyDomain)) {
                $options['proxy'] = $_ENV['HTTP_REQUEST_PROXY'];
                $this->logProxyUsage($options['proxy']);
                break;
            }
        }

        return $options;
    }

    private function logProxyUsage(mixed $proxy): void
    {
        $proxyStr = is_scalar($proxy) ? (string) $proxy : 'unknown';
        $this->logger->debug('当前HTTP代理:' . $proxyStr);
    }
}
