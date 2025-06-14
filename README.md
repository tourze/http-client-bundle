# HttpClientBundle

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/http-client-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/http-client-bundle)
[![Build Status](https://img.shields.io/travis/tourze/http-client-bundle/master.svg?style=flat-square)](https://travis-ci.org/tourze/http-client-bundle)
[![Quality Score](https://img.shields.io/scrutinizer/g/tourze/http-client-bundle.svg?style=flat-square)](https://scrutinizer-ci.com/g/tourze/http-client-bundle)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/http-client-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/http-client-bundle)

A powerful Symfony HTTP client bundle with smart implementation selection, request caching, distributed locking, retry, detailed logging, coroutine support, DNS cache, async requests, and event-driven extensibility.

---

## Features

- Smart HTTP client, auto-selects best implementation (curl/native)
- Request caching for efficient API data retrieval
- Distributed lock to prevent duplicate requests
- Automatic retry for transient errors
- Full request/response logging
- Coroutine support (prevents curl instance sharing)
- DNS resolution cache
- Asynchronous request support
- Event system for request/response hooks

## Installation

- PHP >= 8.1
- Symfony >= 6.4

Install via Composer:

```bash
composer require tourze/http-client-bundle
```

## Quick Start

### 1. Create an API Client

Extend the `ApiClient` class and implement required methods:

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
        return 'POST';
    }
}
```

### 2. Create a Request Class

#### Basic Request

```php
use HttpClientBundle\Request\RequestInterface;

class MyApiRequest implements RequestInterface
{
    private string $path;
    public function __construct(string $path)
    {
        $this->path = $path;
    }
    public function getRequestPath(): string
    {
        return $this->path;
    }
    public function getRequestOptions(): ?array
    {
        return null;
    }
    public function getRequestMethod(): ?string
    {
        return null;
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
        return 'my-api-cache-key';
    }
    public function getCacheDuration(): int
    {
        return 3600; // cache for 1 hour
    }
}
```

#### Request with Distributed Lock

```php
use HttpClientBundle\Request\LockRequest;
class LockedApiRequest implements RequestInterface, LockRequest
{
    public function getLockKey(): string
    {
        return 'my-api-lock-key';
    }
}
```

#### Auto-Retry Request

```php
use HttpClientBundle\Request\AutoRetryRequest;
class RetryableApiRequest implements RequestInterface, AutoRetryRequest
{
    public function getMaxRetries(): int
    {
        return 3; // maximum 3 retries
    }
}
```

### 3. Send a Request

```php
class MyService
{
    public function __construct(private MyApiClient $client) {}
    public function callApi(): array
    {
        $request = new MyApiRequest('endpoint');
        $response = $this->client->request($request);
        return $response->toArray();
    }
}
```

### 4. Asynchronous Requests

Use the `@Async` attribute to enable async requests:

```php
use Tourze\Symfony\AopAsyncBundle\Attribute\Async;
class MyAsyncService
{
    public function __construct(private MyApiClient $client) {}
    #[Async]
    public function callApiAsync(): void
    {
        $request = new MyApiRequest('endpoint');
        $this->client->request($request);
    }
}
```

### 5. Event Listeners

You can listen to request and response events:

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
        // handle request event
    }
    public function onResponse(ResponseEvent $event): void
    {
        // handle response event
    }
}
```

## Documentation

- API docs: See source code for detailed interfaces and advanced usage.
- Configurations: Customize caching, locking, retry, and async via Symfony config.
- [Workflow Diagram](WORKFLOW.md): See the full request processing flow.
- [Entity Design](ENTITY_DESIGN.zh-CN.md): Database entity details.

## Contribution

1. Fork the repo, create a feature branch.
2. Submit issues and pull requests with clear descriptions.
3. Follow PSR coding standards and ensure tests pass (`phpunit`).

## License

MIT License. See [LICENSE](../../LICENSE).

## Author

tourze <https://github.com/tourze>

## Changelog

See [releases](https://github.com/tourze/http-client-bundle/releases) for version history and upgrade notes.
