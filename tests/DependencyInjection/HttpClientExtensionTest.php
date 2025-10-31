<?php

namespace HttpClientBundle\Tests\DependencyInjection;

use HttpClientBundle\DependencyInjection\HttpClientExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;

/**
 * @internal
 */
#[CoversClass(HttpClientExtension::class)]
final class HttpClientExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
}
