<?php

namespace HttpClientBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineIpBundle\Traits\CreatedFromIpAware;
use Tourze\DoctrineTimestampBundle\Traits\CreateTimeAware;
use Tourze\DoctrineUserAgentBundle\Attribute\CreateUserAgentColumn;
use Tourze\DoctrineUserBundle\Traits\CreatedByAware;
use Tourze\ScheduleEntityCleanBundle\Attribute\AsScheduleClean;

/**
 * 请求外部接口日志
 */
#[AsScheduleClean(expression: '40 1 * * *', defaultKeepDay: 1, keepDayEnv: 'HTTP_REQUEST_LOG_PERSIST_DAY_NUM')]
#[ORM\Entity]
#[ORM\Table(name: 'http_request', options: ['comment' => '请求外部接口日志'])]
class HttpRequestLog
{
    use CreateTimeAware;
    use CreatedByAware;
    use CreatedFromIpAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[Assert\Url]
    #[Assert\Length(max: 512)]
    #[IndexColumn]
    #[ORM\Column(length: 512, options: ['comment' => '请求链接'])]
    private ?string $requestUrl = null;

    #[Assert\Length(max: 20)]
    #[ORM\Column(length: 20, nullable: true, options: ['comment' => '请求方式'])]
    private ?string $method = null;

    #[Assert\Length(max: 65535)]
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '请求内容'])]
    private ?string $content = null;

    #[Assert\Length(max: 65535)]
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '响应内容'])]
    private ?string $response = null;

    #[Assert\Length(max: 65535)]
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '异常'])]
    private ?string $exception = null;

    #[Assert\Length(max: 15)]
    #[Assert\Regex(pattern: '/^\d+\.\d{2}$/', message: 'Duration must be a decimal number with 2 decimal places')]
    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, nullable: true, options: ['comment' => '执行时长'])]
    private ?string $stopwatchDuration = null;

    /**
     * @var array<array-key, mixed>|null
     */
    #[Assert\Type(type: 'array')]
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '原始请求对象'])]
    private ?array $requestOptions = null;

    #[Assert\Length(max: 65535)]
    #[CreateUserAgentColumn]
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '创建时UA'])]
    private ?string $createdFromUa = null;

    public function __toString(): string
    {
        if (null === $this->id) {
            return 'New HTTP Request Log';
        }

        return sprintf('HTTP Request Log #%d - %s', $this->id, $this->requestUrl ?? 'Unknown URL');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getResponse(): ?string
    {
        return $this->response;
    }

    public function setResponse(?string $response): void
    {
        $this->response = $response;
    }

    public function getException(): ?string
    {
        return $this->exception;
    }

    public function setException(?string $exception): void
    {
        $this->exception = $exception;
    }

    public function renderStatus(): string
    {
        return null !== $this->getException() ? '异常' : '成功';
    }

    public function getRequestUrl(): ?string
    {
        return $this->requestUrl;
    }

    public function setRequestUrl(string $requestUrl): void
    {
        $this->requestUrl = $requestUrl;
    }

    public function getMethod(): ?string
    {
        return $this->method;
    }

    public function setMethod(?string $method): void
    {
        $this->method = $method;
    }

    public function getStopwatchDuration(): ?string
    {
        return $this->stopwatchDuration;
    }

    public function setStopwatchDuration(?string $stopwatchDuration): void
    {
        $this->stopwatchDuration = $stopwatchDuration;
    }

    /**
     * @return array<array-key, mixed>|null
     */
    public function getRequestOptions(): ?array
    {
        return $this->requestOptions;
    }

    /**
     * @param array<array-key, mixed>|null $requestOptions
     */
    public function setRequestOptions(?array $requestOptions): void
    {
        $this->requestOptions = $requestOptions;
    }

    public function getCreatedFromUa(): ?string
    {
        return $this->createdFromUa;
    }

    public function setCreatedFromUa(?string $createdFromUa): void
    {
        $this->createdFromUa = $createdFromUa;
    }
}
