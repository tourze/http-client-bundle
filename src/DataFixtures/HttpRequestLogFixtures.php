<?php

declare(strict_types=1);

namespace HttpClientBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use HttpClientBundle\Entity\HttpRequestLog;
use Symfony\Component\DependencyInjection\Attribute\When;

/**
 * HTTP 请求日志数据填充
 *
 * 创建不同类型的 HTTP 请求日志数据，包括成功请求、失败请求和异常请求
 */
#[When(env: 'test')]
#[When(env: 'dev')]
class HttpRequestLogFixtures extends Fixture implements FixtureGroupInterface
{
    private const HTTP_METHODS = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];

    private const SAMPLE_URLS = [
        'https://api.github.com/repos/symfony/symfony',
        'https://jsonplaceholder.typicode.com/posts/1',
        'https://httpbin.org/json',
        'https://api.openweathermap.org/data/2.5/weather',
        'https://reqres.in/api/users',
        'https://api.stripe.com/v1/charges',
        'https://graph.microsoft.com/v1.0/me',
        'https://api.dropbox.com/2/files/list_folder',
        'https://api.twitter.com/2/tweets',
        'https://slack.com/api/conversations.list',
    ];

    private const SAMPLE_RESPONSES = [
        '{"status": "success", "data": {"id": 1, "name": "test"}}',
        '{"error": false, "message": "Request processed successfully"}',
        '{"users": [{"id": 1, "email": "test@test.local"}]}',
        '{"weather": {"main": "Clear", "temp": 25}}',
        '{"token": "abc123", "expires_in": 3600}',
    ];

    private const SAMPLE_EXCEPTIONS = [
        'Connection timeout after 30 seconds',
        'HTTP 404 Not Found: Resource not available',
        'HTTP 500 Internal Server Error: Server processing failed',
        'SSL certificate verification failed',
        'Network unreachable: Connection refused',
        'DNS resolution failed for domain',
        'HTTP 429 Too Many Requests: Rate limit exceeded',
        'HTTP 401 Unauthorized: Invalid authentication',
    ];

    private const USER_AGENTS = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36',
        'curl/7.68.0',
        'Symfony HttpClient/6.4',
        'PostmanRuntime/7.29.0',
    ];

    public const REQUEST_LOG_REFERENCE_PREFIX = 'http-request-log-';

    public function load(ObjectManager $manager): void
    {
        $logCount = 0;

        // 创建成功请求日志 (70%)
        for ($i = 1; $i <= 35; ++$i) {
            $log = $this->createSuccessfulRequest();
            $manager->persist($log);
            $this->addReference(self::REQUEST_LOG_REFERENCE_PREFIX . ++$logCount, $log);
        }

        // 创建失败请求日志 (20%)
        for ($i = 1; $i <= 10; ++$i) {
            $log = $this->createFailedRequest();
            $manager->persist($log);
            $this->addReference(self::REQUEST_LOG_REFERENCE_PREFIX . ++$logCount, $log);
        }

        // 创建异常请求日志 (10%)
        for ($i = 1; $i <= 5; ++$i) {
            $log = $this->createExceptionRequest();
            $manager->persist($log);
            $this->addReference(self::REQUEST_LOG_REFERENCE_PREFIX . ++$logCount, $log);
        }

        $manager->flush();
    }

    private function createSuccessfulRequest(): HttpRequestLog
    {
        $log = new HttpRequestLog();

        $log->setRequestUrl(self::SAMPLE_URLS[array_rand(self::SAMPLE_URLS)]);
        $log->setMethod(self::HTTP_METHODS[array_rand(self::HTTP_METHODS)]);
        $log->setContent($this->generateRequestContent());
        $log->setResponse(self::SAMPLE_RESPONSES[array_rand(self::SAMPLE_RESPONSES)]);
        $log->setStopwatchDuration($this->generateDuration(0.1, 2.0));
        $log->setRequestOptions($this->generateRequestOptions());
        $log->setCreatedFromUa(self::USER_AGENTS[array_rand(self::USER_AGENTS)]);

        return $log;
    }

    private function createFailedRequest(): HttpRequestLog
    {
        $log = new HttpRequestLog();

        $log->setRequestUrl(self::SAMPLE_URLS[array_rand(self::SAMPLE_URLS)]);
        $log->setMethod(self::HTTP_METHODS[array_rand(self::HTTP_METHODS)]);
        $log->setContent($this->generateRequestContent());
        $log->setResponse('{"error": true, "message": "Request failed"}');
        $log->setStopwatchDuration($this->generateDuration(2.0, 10.0));
        $log->setRequestOptions($this->generateRequestOptions());
        $log->setCreatedFromUa(self::USER_AGENTS[array_rand(self::USER_AGENTS)]);

        return $log;
    }

    private function createExceptionRequest(): HttpRequestLog
    {
        $log = new HttpRequestLog();

        $log->setRequestUrl(self::SAMPLE_URLS[array_rand(self::SAMPLE_URLS)]);
        $log->setMethod(self::HTTP_METHODS[array_rand(self::HTTP_METHODS)]);
        $log->setContent($this->generateRequestContent());
        $log->setException(self::SAMPLE_EXCEPTIONS[array_rand(self::SAMPLE_EXCEPTIONS)]);
        $log->setStopwatchDuration($this->generateDuration(5.0, 30.0));
        $log->setRequestOptions($this->generateRequestOptions());
        $log->setCreatedFromUa(self::USER_AGENTS[array_rand(self::USER_AGENTS)]);

        return $log;
    }

    private function generateRequestContent(): string
    {
        $contentTypes = [
            '{"query": "test", "limit": 10}',
            '{"email": "user@test.local", "password": "secret"}',
            '{"name": "Test User", "age": 25, "city": "Beijing"}',
            'username=test&password=123456',
            'q=search+term&sort=date&order=desc',
        ];

        return $contentTypes[array_rand($contentTypes)];
    }

    private function generateDuration(float $min, float $max): string
    {
        $duration = round(mt_rand((int) ($min * 100), (int) ($max * 100)) / 100, 2);

        return number_format($duration, 2, '.', '');
    }

    /**
     * @return array<string, mixed>
     */
    private function generateRequestOptions(): array
    {
        $options = [
            'timeout' => mt_rand(5, 30),
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'User-Agent' => self::USER_AGENTS[array_rand(self::USER_AGENTS)],
            ],
        ];

        // 随机添加一些选项
        if (1 === mt_rand(0, 1)) {
            $options['verify'] = true;
        }

        if (1 === mt_rand(0, 1)) {
            $options['auth_basic'] = 'username:password';
        }

        return $options;
    }

    public static function getGroups(): array
    {
        return ['http-client'];
    }
}
