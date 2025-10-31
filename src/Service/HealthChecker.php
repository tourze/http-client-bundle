<?php

declare(strict_types=1);

namespace HttpClientBundle\Service;

use DateTimeImmutable;
use Laminas\Diagnostics\Result\Failure;
use Laminas\Diagnostics\Result\ResultInterface;
use Laminas\Diagnostics\Result\Skip;
use Laminas\Diagnostics\Result\Success;
use Laminas\Diagnostics\Result\Warning;
use League\Uri\Uri;
use Spatie\SslCertificate\SslCertificate;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * 健康检查服务
 */
class HealthChecker
{
    private const DEFAULT_HTTP_PORT = 80;
    private const DEFAULT_HTTPS_PORT = 443;
    private const SSL_WARNING_DAYS = 7;
    private const PORT_CHECK_TIMEOUT = 3;

    public function check(string $baseUrl, HttpClientInterface $httpClient): ResultInterface
    {
        if ('' === $baseUrl) {
            return new Skip('未实现getBaseUrl，不处理');
        }

        $uri = Uri::new($baseUrl);
        $host = $uri->getHost();
        $port = $uri->getPort() ?? ('https' === $uri->getScheme() ? self::DEFAULT_HTTPS_PORT : self::DEFAULT_HTTP_PORT);

        if (!method_exists($httpClient, 'refreshDomainResolveCache')) {
            return new Skip('HTTP客户端不支持域名解析缓存功能');
        }

        $ip = $httpClient->refreshDomainResolveCache($host ?? '');
        if (!is_string($ip) || $ip === $host) {
            return new Failure("{$host}:解析DNS失败");
        }

        if (!$this->isPortOpen($ip, $port, self::PORT_CHECK_TIMEOUT)) {
            return new Failure("{$host}({$ip}):{$port}端口不通");
        }

        return $this->checkSslCertificate($host, $ip, $port);
    }

    private function isPortOpen(string $ip, int $port, int $timeout): bool
    {
        $connection = @fsockopen($ip, $port, $errno, $errstr, $timeout);

        if (is_resource($connection)) {
            fclose($connection);

            return true;
        }

        return false;
    }

    private function checkSslCertificate(?string $host, string $ip, int $port): ResultInterface
    {
        if (null === $host) {
            return new Failure('无效的主机名');
        }

        $certificate = SslCertificate::createForHostName($host);
        if (!($certificate instanceof SslCertificate) || !$certificate->isValid()) {
            return new Failure("域名SSL证书已过期[{$host}]");
        }

        $now = new \DateTimeImmutable();
        $daysUntilExpiration = $certificate->expirationDate()->diff($now)->days;

        if (is_int($daysUntilExpiration) && $daysUntilExpiration <= self::SSL_WARNING_DAYS) {
            $expirationDate = $certificate->expirationDate()->format('Y-m-d H:i:s');

            return new Warning("域名SSL证书过期提醒[{$host}]将于[{$expirationDate}]过期");
        }

        return new Success("{$host}({$ip}):{$port}端口连接成功");
    }
}
