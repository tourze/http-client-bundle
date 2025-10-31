<?php

declare(strict_types=1);

namespace HttpClientBundle\Tests\Service;

use HttpClientBundle\Service\AdminMenu;
use Knp\Menu\ItemInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;

/**
 * AdminMenu 单元测试
 *
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
final class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    private ItemInterface $item;

    public function testInvokeMethod(): void
    {
        // 测试 AdminMenu 的 __invoke 方法正常工作
        $this->expectNotToPerformAssertions();

        try {
            $adminMenu = self::getService(AdminMenu::class);
            ($adminMenu)($this->item);
        } catch (\Throwable $e) {
            self::fail('AdminMenu __invoke method should not throw exception: ' . $e->getMessage());
        }
    }

    public function testAdminMenuIsCallable(): void
    {
        $adminMenu = self::getService(AdminMenu::class);
        self::assertIsCallable($adminMenu);
    }

    public function testAdminMenuImplementsCorrectInterface(): void
    {
        $adminMenu = self::getService(AdminMenu::class);
        self::assertInstanceOf(MenuProviderInterface::class, $adminMenu);
    }

    public function testAdminMenuCreatesSystemManagementMenu(): void
    {
        $adminMenu = self::getService(AdminMenu::class);

        // 创建一个新的mock来测试没有"系统管理"菜单的情况
        $testItem = $this->createMock(ItemInterface::class);
        $systemManagementItem = $this->createMock(ItemInterface::class);
        $httpRequestLogItem = $this->createMock(ItemInterface::class);

        // 第一次调用getChild返回null（没有"系统管理"菜单），第二次调用返回刚创建的菜单项
        $testItem->method('getChild')
            ->with('系统管理')
            ->willReturnOnConsecutiveCalls(null, $systemManagementItem)
        ;

        $testItem->expects(self::once())
            ->method('addChild')
            ->with('系统管理')
            ->willReturn($systemManagementItem)
        ;

        // 设置系统管理菜单的行为
        $systemManagementItem->method('addChild')
            ->with('HTTP请求日志')
            ->willReturn($httpRequestLogItem)
        ;

        $httpRequestLogItem->method('setUri')->willReturn($httpRequestLogItem);
        $httpRequestLogItem->method('setAttribute')->willReturn($httpRequestLogItem);

        ($adminMenu)($testItem);
    }

    protected function onSetUp(): void
    {
        $this->item = $this->createMock(ItemInterface::class);

        // 设置 mock 的返回值以避免 null 引用
        $childItem = $this->createMock(ItemInterface::class);
        $this->item->method('addChild')->willReturn($childItem);

        // 使用 willReturnCallback 来模拟 getChild 的行为
        $this->item->method('getChild')->willReturnCallback(function ($name) use ($childItem) {
            return '系统管理' === $name ? $childItem : null;
        });

        // 设置子菜单项的 mock 行为
        $childItem->method('addChild')->willReturn($childItem);
        $childItem->method('setUri')->willReturn($childItem);
        $childItem->method('setAttribute')->willReturn($childItem);
    }
}
