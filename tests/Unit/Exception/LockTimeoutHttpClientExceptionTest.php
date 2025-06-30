<?php

namespace HttpClientBundle\Tests\Unit\Exception;

use HttpClientBundle\Exception\LockTimeoutHttpClientException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \HttpClientBundle\Exception\LockTimeoutHttpClientException
 */
class LockTimeoutHttpClientExceptionTest extends TestCase
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
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function testExceptionWithDefaultValues(): void
    {
        $exception = new LockTimeoutHttpClientException();
        
        $this->assertEquals('', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }
}