# HttpClientBundle

[English](README.md) | [中文](README.zh-CN.md)

[![PHP 版本](https://img.shields.io/badge/php-%5E8.1-blue?style=flat-square)](https://php.net)
[![Symfony 版本](https://img.shields.io/badge/symfony-%5E6.4-green?style=flat-square)](https://symfony.com)
[![许可证](https://img.shields.io/badge/license-MIT-brightgreen?style=flat-square)](LICENSE)
[![构建状态](https://img.shields.io/github/workflow/status/tourze/http-client-bundle/CI?style=flat-square)]
(https://github.com/tourze/http-client-bundle/actions)
[![Code Coverage](https://img.shields.io/codecov/c/github/tourze/http-client-bundle?style=flat-square)]
(https://codecov.io/gh/tourze/http-client-bundle)

一个基于 Symfony 的高性能 HTTP 客户端增强包，支持智能实现选择、
请求缓存、分布式锁、重试机制、详细日志、协程支持、DNS 缓存、
异步请求与事件驱动扩展。

## 目录

- [功能特性](#功能特性)
- [安装说明](#安装说明)
- [快速开始](#快速开始)
- [配置说明](#配置说明)
- [依赖包](#依赖包)
- [高级用法](#高级用法)
- [安全性](#安全性)
- [文档](#文档)
- [贡献](#贡献)
- [许可证](#许可证)

## 功能特性

- 智能 HTTP 客户端，自动选择最优实现（curl/native）
- 请求缓存，提升 API 数据获取效率
- 分布式锁机制，防止重复请求
- 自动重试机制，处理临时性故障
- 完整的请求/响应日志与性能指标
- 协程支持，防止 curl 实例共用问题
- DNS 解析缓存，提升性能
- 异步请求支持与事件驱动架构
- 事件系统，支持请求/响应钩子与中间件
- SSL 证书验证与健康检查

## 安装说明

### 系统要求

- PHP >= 8.1
- Symfony >= 6.4

### 通过 Composer 安装

```bash
composer require tourze/http-client-bundle
```

## 快速开始

### 1. 启用 Bundle

在 `config/bundles.php` 中添加：

```php
return [
    // ... 其他 bundles
    HttpClientBundle\HttpClientBundle::class => ['all' => true],
];
```

### 2. 创建 API 客户端

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

### 3. 创建请求类

#### 基础请求
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

#### 缓存请求
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
        return 3600; // 1 小时
    }
}
```

## 配置说明

### 基础配置

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

### 服务配置

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

## 依赖包

本包依赖以下核心组件：

### 核心依赖
- `symfony/http-client`: HTTP 客户端实现
- `symfony/cache`: 缓存功能
- `symfony/lock`: 分布式锁
- `symfony/event-dispatcher`: 事件系统
- `doctrine/orm`: 实体持久化
- `psr/log`: 日志接口

### 可选依赖
- `tourze/symfony-aop-async-bundle`: 异步请求支持
- `tourze/doctrine-async-bundle`: 异步数据库操作
- `spatie/ssl-certificate`: SSL 证书验证

## 高级用法

### 健康检查

Bundle 提供内置的健康检查功能：

```php
class MyApiClient extends ApiClient implements CheckInterface
{
    public function check(): ResultInterface
    {
        // 自动进行 SSL 和连通性检查
        return parent::check();
    }
}
```

### 事件监听器

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
        // 添加认证头
        $options = $event->getOptions();
        $options['headers']['Authorization'] = 'Bearer ' . $this->getToken();
        $event->setOptions($options);
    }
    
    public function onResponse(ResponseEvent $event): void
    {
        // 记录响应指标
        $this->logger->info('API 响应', [
            'status_code' => $event->getResponse()->getStatusCode(),
            'duration' => $event->getDuration(),
        ]);
    }
}
```

### 协程安全使用

适用于 Swoole 或 ReactPHP：

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

## 安全性

### SSL 证书验证

Bundle 自动验证 SSL 证书：

```php
// 健康检查中的自动 SSL 验证
$result = $apiClient->check();
if ($result instanceof Success) {
    // SSL 证书有效
}
```

### 请求签名

实现请求签名以提升 API 安全性：

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

### 限流控制

使用分布式锁进行限流：

```php
class RateLimitedRequest implements RequestInterface, LockRequest
{
    public function getLockKey(): string
    {
        return 'rate_limit_' . $this->getUserId();
    }
}
```

## 文档

- [API 参考](docs/api.md): 完整的 API 文档
- [配置指南](docs/configuration.md): 详细的配置选项
- [性能调优](docs/performance.md): 优化指导
- [故障排除](docs/troubleshooting.md): 常见问题与解决方案

## 贡献

1. Fork 仓库并创建功能分支
2. 为新功能编写测试
3. 确保所有测试通过：`vendor/bin/phpunit`
4. 检查代码质量：`vendor/bin/phpstan analyse`
5. 提交带有清晰描述的 Pull Request

### 开发环境设置

```bash
git clone https://github.com/tourze/http-client-bundle
cd http-client-bundle
composer install
vendor/bin/phpunit
```

## 许可证

MIT 许可证。详情请查看 [LICENSE](LICENSE) 文件。

## 变更日志

版本历史和升级说明请查看 [CHANGELOG.md](CHANGELOG.md)。
