<?php

declare(strict_types=1);

namespace HttpClientBundle\Tests;

use HttpClientBundle\HttpClientBundle;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;

/**
 * @internal
 */
#[CoversClass(HttpClientBundle::class)]
#[RunTestsInSeparateProcesses]
final class HttpClientBundleTest extends AbstractBundleTestCase
{
}
