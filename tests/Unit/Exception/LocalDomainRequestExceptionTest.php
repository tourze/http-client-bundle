<?php

namespace HttpClientBundle\Tests\Unit\Exception;

use HttpClientBundle\Exception\LocalDomainRequestException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \HttpClientBundle\Exception\LocalDomainRequestException
 */
class LocalDomainRequestExceptionTest extends TestCase
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
        $this->assertInstanceOf(\LogicException::class, $exception);
    }

    public function testExceptionWithDefaultValues(): void
    {
        $exception = new LocalDomainRequestException();
        
        $this->assertEquals('', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }
}