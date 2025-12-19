<?php

declare(strict_types=1);

namespace HttpClientBundle\Tests\Client;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * Client 单元测试基类
 *
 * 用于测试 ApiClient、LockHttpClient 等工具类/装饰器。
 * 这些类不是容器注册的服务，因此使用单元测试模式。
 *
 * @internal
 */
#[CoversNothing]
abstract class AbstractClientTestCase extends TestCase
{
}
