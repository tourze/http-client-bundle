# HttpClientBundle

一个基于 Symfony 的 HTTP 客户端增强包，提供智能 HTTP 客户端、缓存、分布式锁、请求重试、日志记录等高级功能。

## 功能特性

- 智能 HTTP 客户端，自动选择最优的实现（curl/native）
- 请求缓存支持，轻松实现接口数据缓存
- 分布式锁机制，防止重复请求
- 自动重试机制，处理临时性故障
- 完整的请求/响应日志记录
- 协程支持，防止 curl 实例共用
- DNS 解析缓存，提高性能
- 异步请求支持
- 事件系统，支持请求/响应事件监听

## 依赖关系

- AppBundle
- DoctrineEnhanceBundle（用于日志记录）

## 使用方法

### 1. 创建 API 客户端

继承 `ApiClient` 类并实现必要的方法：

```php
use HttpClientBundle\Client\ApiClient;
use HttpClientBundle\Request\RequestInterface;

class MyApiClient extends ApiClient
{
    protected function getRequestUrl(RequestInterface $request): string
    {
        return 'https://api.example.com/' . $request->getPath();
    }

    protected function getRequestMethod(RequestInterface $request): string
    {
        return 'POST';
    }
}
```

### 2. 创建请求类

#### 基础请求

```php
use HttpClientBundle\Request\RequestInterface;

class MyApiRequest implements RequestInterface
{
    private string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
```

#### 带缓存的请求

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
        return 3600; // 缓存1小时
    }
}
```

#### 带分布式锁的请求

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

#### 自动重试的请求

```php
use HttpClientBundle\Request\AutoRetryRequest;

class RetryableApiRequest implements RequestInterface, AutoRetryRequest
{
    public function getRetryCount(): int
    {
        return 3; // 最多重试3次
    }

    public function getRetryDelay(): int
    {
        return 1000; // 重试间隔1秒
    }
}
```

### 3. 使用客户端发送请求

```php
class MyService
{
    public function __construct(
        private MyApiClient $client
    ) {}

    public function callApi(): array
    {
        $request = new MyApiRequest('endpoint');
        $response = $this->client->request($request);
        return $response->toArray();
    }
}
```

### 4. 异步请求

使用 `@Async` 注解实现异步请求：

```php
use Tourze\Symfony\Async\Attribute\Async;

class MyAsyncService
{
    public function __construct(
        private MyApiClient $client
    ) {}

    #[Async]
    public function callApiAsync(): void
    {
        $request = new MyApiRequest('endpoint');
        $this->client->request($request);
    }
}
```

### 5. 事件监听

可以通过事件系统监听请求和响应：

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
        // 处理请求事件
    }

    public function onResponse(ResponseEvent $event): void
    {
        // 处理响应事件
    }
}
```
