<?php

namespace HttpClientBundle\Tests\Exception;

use HttpClientBundle\Exception\LocalDomainRequestException;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(LocalDomainRequestException::class)]
final class LocalDomainRequestExceptionTest extends AbstractExceptionTestCase
{
    public function testException(): void
    {
        $message = 'Local domain request not allowed';
        $code = 400;
        $previousException = new \LogicException('Previous exception');

        $exception = new LocalDomainRequestException($message, $code, $previousException);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertSame($previousException, $exception->getPrevious());
    }

    public function testExceptionWithDefaultValues(): void
    {
        $exception = new LocalDomainRequestException();

        $this->assertEquals('', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }
}
