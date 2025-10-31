<?php

namespace HttpClientBundle\Tests\Exception;

use HttpClientBundle\Exception\LockTimeoutHttpClientException;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(LockTimeoutHttpClientException::class)]
final class LockTimeoutHttpClientExceptionTest extends AbstractExceptionTestCase
{
    public function testException(): void
    {
        $message = 'Lock timeout occurred';
        $code = 123;
        $previousException = new \RuntimeException('Previous exception');

        $exception = new LockTimeoutHttpClientException($message, $code, $previousException);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertSame($previousException, $exception->getPrevious());
    }

    public function testExceptionWithDefaultValues(): void
    {
        $exception = new LockTimeoutHttpClientException();

        $this->assertEquals('', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }
}
