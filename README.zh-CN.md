# HttpClientBundle

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/http-client-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/http-client-bundle)
[![Build Status](https://img.shields.io/travis/tourze/http-client-bundle/master.svg?style=flat-square)](https://travis-ci.org/tourze/http-client-bundle)
[![Quality Score](https://img.shields.io/scrutinizer/g/tourze/http-client-bundle.svg?style=flat-square)](https://scrutinizer-ci.com/g/tourze/http-client-bundle)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/http-client-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/http-client-bundle)

一个基于 Symfony 的高性能 HTTP 客户端增强包，支持智能实现选择、请求缓存、分布式锁、自动重试、详细日志、协程、DNS 缓存、异步请求与事件驱动扩展。

---

## 功能特性

- 智能 HTTP 客户端，自动选择最优实现（curl/native）
- 请求缓存，提升 API 数据获取效率
- 分布式锁机制，防止重复请求
- 自动重试，处理临时性故障
- 完整的请求/响应日志
- 协程支持，防止 curl 实例共用
- DNS 解析缓存
- 支持异步请求
- 事件系统，支持请求/响应钩子

## 安装说明

- PHP >= 8.1
- Symfony >= 6.4

使用 Composer 安装：

```bash
composer require tourze/http-client-bundle
```

## 快速开始

### 1. 创建 API 客户端

继承 `ApiClient` 类并实现必要方法：

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

#### 自动重试请求

```php
use HttpClientBundle\Request\AutoRetryRequest;
class RetryableApiRequest implements RequestInterface, AutoRetryRequest
{
    public function getMaxRetries(): int
    {
        return 3; // 最多重试3次
    }
}
```

### 3. 客户端发送请求

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

### 4. 异步请求

使用 `@Async` 注解实现异步请求：

```php
use Tourze\Symfony\Async\Attribute\Async;
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

### 5. 事件监听

可监听请求和响应事件：

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

## 详细文档

- API 文档：详见源码接口和高级用法
- 配置项：可通过 Symfony 配置自定义缓存、锁、重试、异步等
- [工作流程图](WORKFLOW.md)：详见请求处理全流程
- [实体设计](ENTITY_DESIGN.zh-CN.md)：数据库实体说明

## 贡献指南

1. Fork 仓库、新建功能分支
2. 提交 issue 和 PR 时请附详细说明
3. 遵循 PSR 代码规范，确保测试通过（`phpunit`）

## 版权和许可

MIT License，详见 [LICENSE](../../LICENSE)

## 作者信息

tourze <https://github.com/tourze>

## 更新日志

详见 [releases](https://github.com/tourze/http-client-bundle/releases) 获取版本历史与升级说明。
