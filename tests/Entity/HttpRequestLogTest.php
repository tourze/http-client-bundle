<?php

namespace HttpClientBundle\Tests\Entity;

use HttpClientBundle\Entity\HttpRequestLog;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(HttpRequestLog::class)]
final class HttpRequestLogTest extends AbstractEntityTestCase
{
    protected function createEntity(): HttpRequestLog
    {
        return new HttpRequestLog();
    }

    /**
     * @return iterable<array{0: string, 1: mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'content' => ['content', 'test content'];
        yield 'response' => ['response', 'test response'];
        yield 'exception' => ['exception', 'test exception'];
        yield 'requestUrl' => ['requestUrl', 'https://example.com'];
        yield 'method' => ['method', 'POST'];
        yield 'stopwatchDuration' => ['stopwatchDuration', '1.23'];
        yield 'requestOptions' => ['requestOptions', ['timeout' => 30]];
        yield 'createdFromIp' => ['createdFromIp', '192.168.1.1'];
        yield 'createdFromUa' => ['createdFromUa', 'Mozilla/5.0'];
    }

    public function testGetId(): void
    {
        $entity = $this->createEntity();
        $this->assertNull($entity->getId());
    }

    public function testToStringWithId(): void
    {
        $entity = $this->createEntity();
        $reflectionProperty = new \ReflectionProperty(HttpRequestLog::class, 'id');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($entity, 123);

        $entity->setRequestUrl('https://example.com');

        $this->assertEquals('HTTP Request Log #123 - https://example.com', $entity->__toString());
    }

    public function testToStringWithDefaultId(): void
    {
        $entity = $this->createEntity();
        $this->assertEquals('New HTTP Request Log', $entity->__toString());
    }

    public function testRenderStatusWithException(): void
    {
        $entity = $this->createEntity();
        $entity->setException('some error');
        $this->assertEquals('异常', $entity->renderStatus());
    }

    public function testRenderStatusWithoutException(): void
    {
        $entity = $this->createEntity();
        $this->assertEquals('成功', $entity->renderStatus());
    }
}
