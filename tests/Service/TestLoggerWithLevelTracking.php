<?php

namespace HttpClientBundle\Tests\Service;

use Psr\Log\LoggerInterface;

/**
 * 带日志级别追踪的测试用 Logger 实现
 */
class TestLoggerWithLevelTracking implements LoggerInterface
{
    /** @var array<int, array{0: string, 1: string|\Stringable, 2: array<mixed>}> */
    private array $logs = [];

    /** @param array<mixed> $context */
    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    /** @param array<mixed> $context */
    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    /** @param array<mixed> $context */
    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    /** @param array<mixed> $context */
    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    /** @param array<mixed> $context */
    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    /** @param array<mixed> $context */
    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    /** @param array<mixed> $context */
    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    /** @param array<mixed> $context */
    public function debug(string|\Stringable $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    /** @param array<mixed> $context */
    /** @param mixed $level */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $levelString = match (true) {
            is_string($level) => $level,
            is_int($level) => (string) $level,
            default => 'unknown',
        };
        $this->logs[] = [$levelString, $message, $context];
    }

    /** @return array<int, array{0: string, 1: string|\Stringable, 2: array<mixed>}> */
    public function getLogs(): array
    {
        return $this->logs;
    }

    /** @return array<int, array{0: string, 1: string|\Stringable, 2: array<mixed>}> */
    public function getLogsByLevel(string $level): array
    {
        return array_filter($this->logs, fn (array $log): bool => $log[0] === $level);
    }
}
