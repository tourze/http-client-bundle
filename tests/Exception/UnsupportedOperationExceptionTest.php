<?php

namespace HttpClientBundle\Tests\Exception;

use HttpClientBundle\Exception\UnsupportedOperationException;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(UnsupportedOperationException::class)]
final class UnsupportedOperationExceptionTest extends AbstractExceptionTestCase
{
    public function testException(): void
    {
        $message = 'Operation not supported';
        $code = 500;
        $previousException = new \RuntimeException('Previous exception');

        $exception = new UnsupportedOperationException($message, $code, $previousException);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertSame($previousException, $exception->getPrevious());
    }

    public function testExceptionWithDefaultValues(): void
    {
        $exception = new UnsupportedOperationException();

        $this->assertEquals('', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }
}
