# HttpClientBundle

[English](README.md) | [中文](README.zh-CN.md)

[![PHP Version](https://img.shields.io/badge/php-%5E8.1-blue?style=flat-square)](https://php.net)
[![Symfony Version](https://img.shields.io/badge/symfony-%5E6.4-green?style=flat-square)](https://symfony.com)
[![License](https://img.shields.io/badge/license-MIT-brightgreen?style=flat-square)](LICENSE)
[![Build Status](https://img.shields.io/github/workflow/status/tourze/http-client-bundle/CI?style=flat-square)]
(https://github.com/tourze/http-client-bundle/actions)
[![Code Coverage](https://img.shields.io/codecov/c/github/tourze/http-client-bundle?style=flat-square)]
(https://codecov.io/gh/tourze/http-client-bundle)

A powerful Symfony HTTP client bundle with smart implementation selection, 
request caching, distributed locking, retry mechanisms, detailed logging, 
coroutine support, DNS cache, async requests, and event-driven extensibility.

## Table of Contents

- [Features](#features)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Configuration](#configuration)
- [Dependencies](#dependencies)
- [Advanced Usage](#advanced-usage)
- [Security](#security)
- [Documentation](#documentation)
- [Contribution](#contribution)
- [License](#license)

## Features

- Smart HTTP client with auto-selection of best implementation (curl/native)
- Request caching for efficient API data retrieval
- Distributed lock to prevent duplicate requests
- Automatic retry mechanisms for transient errors
- Full request/response logging with detailed metrics
- Coroutine support (prevents curl instance sharing)
- DNS resolution caching for improved performance
- Asynchronous request support with event-driven architecture
- Event system for request/response hooks and middleware
- SSL certificate validation and health checks

## Installation

### Requirements

- PHP >= 8.1
- Symfony >= 6.4

### Install via Composer

```bash
composer require tourze/http-client-bundle
```

## Quick Start

### 1. Enable the Bundle

Add to `config/bundles.php`:

```php
return [
    // ... other bundles
    HttpClientBundle\HttpClientBundle::class => ['all' => true],
];
```

### 2. Create an API Client

```php
use HttpClientBundle\Client\ApiClient;
use HttpClientBundle\Request\RequestInterface;

class MyApiClient extends ApiClient
{
    protected function getRequestUrl(RequestInterface $request): string
    {
        return 'https://api.example.com/' . $request->getRequestPath();
    }
    
    protected function getRequestMethod(RequestInterface $request): string
    {
        return $request->getRequestMethod() ?? 'GET';
    }
}
```

### 3. Create Request Classes

#### Basic Request
```php
use HttpClientBundle\Request\RequestInterface;

class MyApiRequest implements RequestInterface
{
    public function __construct(private string $path) {}
    
    public function getRequestPath(): string
    {
        return $this->path;
    }
    
    public function getRequestOptions(): ?array
    {
        return ['timeout' => 30];
    }
    
    public function getRequestMethod(): ?string
    {
        return 'GET';
    }
}
```

#### Cached Request
```php
use HttpClientBundle\Request\CacheRequest;

class CachedApiRequest implements RequestInterface, CacheRequest
{
    public function getCacheKey(): string
    {
        return 'api-cache-' . md5($this->getRequestPath());
    }
    
    public function getCacheDuration(): int
    {
        return 3600; // 1 hour
    }
}
```

## Configuration

### Basic Configuration

```yaml
# config/packages/http_client_bundle.yaml
http_client:
    logging:
        enabled: true
        persist_days: 7
    cache:
        default_ttl: 3600
    lock:
        timeout: 30
    retry:
        max_attempts: 3
        delay: 1000
```

### Service Configuration

```yaml
# config/services.yaml
services:
    app.my_api_client:
        class: App\Client\MyApiClient
        arguments:
            $httpClient: '@http_client_bundle.smart_http_client'
            $cache: '@cache.app'
            $lockFactory: '@lock.factory'
```

## Dependencies

This bundle depends on several core packages:

### Core Dependencies
- `symfony/http-client`: HTTP client implementation
- `symfony/cache`: Caching functionality
- `symfony/lock`: Distributed locking
- `symfony/event-dispatcher`: Event system
- `doctrine/orm`: Entity persistence
- `psr/log`: Logging interface

### Optional Dependencies
- `tourze/symfony-aop-async-bundle`: Async request support
- `tourze/doctrine-async-bundle`: Async database operations
- `spatie/ssl-certificate`: SSL certificate validation

## Advanced Usage

### Health Checks

The bundle provides built-in health check functionality:

```php
class MyApiClient extends ApiClient implements CheckInterface
{
    public function check(): ResultInterface
    {
        // Automatic SSL and connectivity checks
        return parent::check();
    }
}
```

### Event Listeners

```php
use HttpClientBundle\Event\RequestEvent;
use HttpClientBundle\Event\ResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ApiEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => 'onRequest',
            ResponseEvent::class => 'onResponse',
        ];
    }
    
    public function onRequest(RequestEvent $event): void
    {
        // Add authentication headers
        $options = $event->getOptions();
        $options['headers']['Authorization'] = 'Bearer ' . $this->getToken();
        $event->setOptions($options);
    }
    
    public function onResponse(ResponseEvent $event): void
    {
        // Log response metrics
        $this->logger->info('API Response', [
            'status_code' => $event->getResponse()->getStatusCode(),
            'duration' => $event->getDuration(),
        ]);
    }
}
```

### Coroutine-Safe Usage

For use with Swoole or ReactPHP:

```php
use HttpClientBundle\Client\CoroutineSafeHttpClient;

class MyCoroutineApiClient extends ApiClient
{
    protected function createHttpClient(): HttpClientInterface
    {
        return new CoroutineSafeHttpClient($this->getInnerHttpClient());
    }
}
```

## Security

### SSL Certificate Validation

The bundle automatically validates SSL certificates:

```php
// Automatic SSL validation in health checks
$result = $apiClient->check();
if ($result instanceof Success) {
    // SSL certificate is valid
}
```

### Request Signing

Implement request signing for API security:

```php
class SignedApiRequest implements RequestInterface
{
    public function getRequestOptions(): ?array
    {
        $timestamp = time();
        $signature = hash_hmac('sha256', $this->getBody() . $timestamp, $this->secretKey);
        
        return [
            'headers' => [
                'X-Timestamp' => $timestamp,
                'X-Signature' => $signature,
            ],
        ];
    }
}
```

### Rate Limiting

Use distributed locks for rate limiting:

```php
class RateLimitedRequest implements RequestInterface, LockRequest
{
    public function getLockKey(): string
    {
        return 'rate_limit_' . $this->getUserId();
    }
}
```

## Documentation

- [API Reference](docs/api.md): Complete API documentation
- [Configuration Guide](docs/configuration.md): Detailed configuration options
- [Performance Tuning](docs/performance.md): Optimization guidelines
- [Troubleshooting](docs/troubleshooting.md): Common issues and solutions

## Contribution

1. Fork the repository and create a feature branch
2. Write tests for new functionality
3. Ensure all tests pass: `vendor/bin/phpunit`
4. Check code quality: `vendor/bin/phpstan analyse`
5. Submit a pull request with clear description

### Development Setup

```bash
git clone https://github.com/tourze/http-client-bundle
cd http-client-bundle
composer install
vendor/bin/phpunit
```

## License

MIT License. See [LICENSE](LICENSE) file for details.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history and upgrade notes.
