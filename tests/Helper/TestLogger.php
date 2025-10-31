<?php

declare(strict_types=1);

namespace HttpClientBundle\Tests\Helper;

use Psr\Log\LoggerInterface;

/**
 * 测试用 Logger 实现
 */
class TestLogger implements LoggerInterface
{
    /** @var list<array{level: mixed, message: string, context: array<mixed>}> */
    private array $logs = [];

    public function emergency(\Stringable|string $message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    public function alert(\Stringable|string $message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    public function critical(\Stringable|string $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    public function error(\Stringable|string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function warning(\Stringable|string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function notice(\Stringable|string $message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    public function info(\Stringable|string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function debug(\Stringable|string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $this->logs[] = ['level' => $level, 'message' => (string) $message, 'context' => $context];
    }

    /**
     * @return array<mixed>
     */
    public function getLogs(): array
    {
        return $this->logs;
    }
}
