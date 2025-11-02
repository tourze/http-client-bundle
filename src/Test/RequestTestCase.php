<?php

declare(strict_types=1);

namespace HttpClientBundle\Test;

use HttpClientBundle\Request\RequestInterface;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * 请求相关测试的统一基类，提供常用断言方法以保持测试风格一致。
 * 这是一个抽象测试基类，不直接测试任何具体的类
 */
#[CoversNothing]
abstract class RequestTestCase extends TestCase
{
    /**
     * 设置测试环境，调用子类的 onSetUp 方法
     */
    protected function setUp(): void
    {
        parent::setUp();
        if (method_exists($this, 'onSetUp')) {
            $this->onSetUp();
        }
    }

    /**
     * 辅助断言请求对象的核心信息，避免重复代码。
     *
     * @param array<array-key, mixed>|null $expectedOptions
     */
    protected function assertRequestEquals(
        string $expectedPath,
        ?array $expectedOptions,
        ?string $expectedMethod,
        RequestInterface $request,
        string $message = '',
    ): void {
        $this->assertSame($expectedPath, $request->getRequestPath(), '' !== $message ? $message : '请求路径不符合预期');
        $this->assertSame($expectedOptions, $request->getRequestOptions(), '' !== $message ? $message : '请求参数不符合预期');
        $this->assertSame($expectedMethod, $request->getRequestMethod(), '' !== $message ? $message : '请求方法不符合预期');
    }
}
