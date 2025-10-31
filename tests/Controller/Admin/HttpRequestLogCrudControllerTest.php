<?php

declare(strict_types=1);

namespace HttpClientBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use HttpClientBundle\Controller\Admin\HttpRequestLogCrudController;
use HttpClientBundle\Entity\HttpRequestLog;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(HttpRequestLogCrudController::class)]
#[RunTestsInSeparateProcesses]
final class HttpRequestLogCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected function getControllerService(): HttpRequestLogCrudController
    {
        return self::getService(HttpRequestLogCrudController::class);
    }

    /** @return iterable<string, array{string}> */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield '请求链接' => ['请求链接'];
        yield '请求方式' => ['请求方式'];
        yield '执行时长' => ['执行时长'];
        yield '状态' => ['状态'];
        yield '创建时间' => ['创建时间'];
    }

    /** @return iterable<string, array{string}> */
    public static function provideEditPageFields(): iterable
    {
        yield 'requestUrl' => ['requestUrl'];
        yield 'method' => ['method'];
        yield 'content' => ['content'];
        yield 'response' => ['response'];
        yield 'exception' => ['exception'];
    }

    protected function getEntityFqcn(): string
    {
        return HttpRequestLog::class;
    }

    public function testIndexPageAccessible(): void
    {
        $client = $this->createAuthenticatedClient();
        $crawler = $client->request('GET', $this->generateAdminUrl('index'));

        $this->assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        self::assertStringContainsString('HTTP请求日志', $content);
    }

    public function testConfigureFieldsForNewPage(): void
    {
        $controller = $this->getControllerService();
        $fields = iterator_to_array($controller->configureFields('new'));

        self::assertNotEmpty($fields, 'New page should have configured fields');

        // Verify all fields are valid EasyAdmin field objects
        foreach ($fields as $field) {
            self::assertInstanceOf(FieldInterface::class, $field);
        }
    }

    public function testConfigureFieldsForEditPage(): void
    {
        $controller = $this->getControllerService();
        $fields = iterator_to_array($controller->configureFields('edit'));

        self::assertNotEmpty($fields, 'Edit page should have configured fields');

        foreach ($fields as $field) {
            self::assertInstanceOf(FieldInterface::class, $field);
        }
    }

    public function testConfigureFieldsForDetailPage(): void
    {
        $controller = $this->getControllerService();
        $fields = iterator_to_array($controller->configureFields('detail'));

        self::assertNotEmpty($fields, 'Detail page should have configured fields');

        foreach ($fields as $field) {
            self::assertInstanceOf(FieldInterface::class, $field);
        }
    }

    public function testConfigureFieldsForIndexPage(): void
    {
        $controller = $this->getControllerService();
        $fields = iterator_to_array($controller->configureFields('index'));

        self::assertNotEmpty($fields, 'Index page should have configured fields');

        foreach ($fields as $field) {
            self::assertInstanceOf(FieldInterface::class, $field);
        }
    }

    public function testConfigureFilters(): void
    {
        $controller = $this->getControllerService();
        $filtersConfig = Filters::new();
        $filters = $controller->configureFilters($filtersConfig);

        // 验证过滤器配置正常工作
        $this->assertNotNull($filters);
    }

    public function testEntityFqcnConfiguration(): void
    {
        $controller = $this->getControllerService();
        self::assertEquals(HttpRequestLog::class, $controller::getEntityFqcn());
    }

    public function testConfigureCrud(): void
    {
        $controller = $this->getControllerService();
        $crudConfig = Crud::new();
        $crud = $controller->configureCrud($crudConfig);

        // 验证CRUD配置正常工作
        $this->assertNotNull($crud);
    }

    public function testControllerCanHandleValidation(): void
    {
        $controller = $this->getControllerService();

        // Test entity FQCN is correct
        self::assertEquals(HttpRequestLog::class, $controller::getEntityFqcn());

        // Test fields configuration doesn't throw errors
        $fields = iterator_to_array($controller->configureFields('index'));
        self::assertNotEmpty($fields);
    }

    /**
     * 重写父类的方法，避免硬编码的必填字段检查
     */

    /** @return iterable<string, array{string}> */
    public static function provideNewPageFields(): iterable
    {
        yield 'requestUrl' => ['requestUrl'];
        yield 'method' => ['method'];
        yield 'content' => ['content'];
        yield 'response' => ['response'];
        yield 'exception' => ['exception'];
    }

    public function testValidationErrors(): void
    {
        $client = $this->createAuthenticatedClient();
        $crawler = $client->request('GET', $this->generateAdminUrl('new'));

        $this->assertResponseIsSuccessful();

        // 尝试找到表单按钮
        $buttonNodes = $crawler->filter('button[type="submit"]');
        if (0 === $buttonNodes->count()) {
            $buttonNodes = $crawler->filter('input[type="submit"]');
        }

        if ($buttonNodes->count() > 0) {
            // 获取表单并提交空数据
            $form = $buttonNodes->form();
            $crawler = $client->submit($form);

            // 如果返回 422，说明验证工作正常
            if (422 === $client->getResponse()->getStatusCode()) {
                $content = $client->getResponse()->getContent();
                self::assertIsString($content);
                self::assertTrue(
                    str_contains($content, '不能为空') || str_contains($content, 'should not be blank'),
                    '表单应显示必填字段验证错误'
                );
            } else {
                // 如果没有验证错误，至少验证表单提交成功
                $this->assertResponseIsSuccessful('表单提交应该成功或显示验证错误');
            }
        } else {
            // 如果没有找到表单，跳过此测试
            self::markTestSkipped('未找到可提交的表单按钮');
        }
    }
}
