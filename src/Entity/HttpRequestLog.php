<?php

namespace HttpClientBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineIpBundle\Attribute\CreateIpColumn;
use Tourze\DoctrineTimestampBundle\Attribute\CreateTimeColumn;
use Tourze\DoctrineUserAgentBundle\Attribute\CreateUserAgentColumn;
use Tourze\DoctrineUserBundle\Attribute\CreatedByColumn;
use Tourze\EasyAdmin\Attribute\Column\ExportColumn;
use Tourze\EasyAdmin\Attribute\Column\ListColumn;
use Tourze\EasyAdmin\Attribute\Permission\AsPermission;
use Tourze\ScheduleEntityCleanBundle\Attribute\AsScheduleClean;

/**
 * 请求外部接口日志
 */
#[AsScheduleClean(expression: '40 1 * * *', defaultKeepDay: 1, keepDayEnv: 'HTTP_REQUEST_LOG_PERSIST_DAY_NUM')]
#[AsPermission(title: '请求外部接口日志')]
#[ORM\Entity]
#[ORM\Table(name: 'http_request', options: ['comment' => '请求外部接口日志'])]
class HttpRequestLog
{
    #[ListColumn(order: -1)]
    #[ExportColumn]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    private ?int $id = 0;

    #[IndexColumn]
    #[ListColumn]
    #[ORM\Column(length: 512, options: ['comment' => '请求链接'])]
    private ?string $requestUrl = null;

    #[ListColumn]
    #[ORM\Column(length: 20, nullable: true, options: ['comment' => '请求方式'])]
    private ?string $method = null;

    #[ListColumn]
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '请求内容'])]
    private ?string $content = null;

    #[ListColumn]
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '响应内容'])]
    private ?string $response = null;

    #[ListColumn]
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '异常'])]
    private ?string $exception = null;

    #[ListColumn]
    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, nullable: true, options: ['comment' => '执行时长'])]
    private ?string $stopwatchDuration = null;

    #[ListColumn]
    #[ORM\Column(nullable: true, options: ['comment' => '原始请求对象'])]
    private ?array $requestOptions = null;

    #[IndexColumn]
    #[ListColumn(order: 98, sorter: true)]
    #[ExportColumn]
    #[CreateTimeColumn]
    #[Groups(['restful_read', 'admin_curd'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '创建时间'])]
    private ?\DateTimeInterface $createTime = null;

    #[CreatedByColumn]
    #[ORM\Column(nullable: true, options: ['comment' => '创建人'])]
    private ?string $createdBy = null;

    #[ListColumn(order: 99)]
    #[CreateIpColumn]
    #[ORM\Column(length: 45, nullable: true, options: ['comment' => '创建时IP'])]
    private ?string $createdFromIp = null;

    #[CreateUserAgentColumn]
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '创建时UA'])]
    private ?string $createdFromUa = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getResponse(): ?string
    {
        return $this->response;
    }

    public function setResponse(?string $response): self
    {
        $this->response = $response;

        return $this;
    }

    public function getException(): ?string
    {
        return $this->exception;
    }

    public function setException(?string $exception): self
    {
        $this->exception = $exception;

        return $this;
    }

    #[ExportColumn(title: '状态')]
    #[ListColumn(title: '状态')]
    public function renderStatus(): string
    {
        return $this->getException() ? '异常' : '成功';
    }

    public function getRequestUrl(): ?string
    {
        return $this->requestUrl;
    }

    public function setRequestUrl(string $requestUrl): self
    {
        $this->requestUrl = $requestUrl;

        return $this;
    }

    public function getMethod(): ?string
    {
        return $this->method;
    }

    public function setMethod(?string $method): self
    {
        $this->method = $method;

        return $this;
    }

    public function getStopwatchDuration(): ?string
    {
        return $this->stopwatchDuration;
    }

    public function setStopwatchDuration(?string $stopwatchDuration): void
    {
        $this->stopwatchDuration = $stopwatchDuration;
    }

    public function getRequestOptions(): ?array
    {
        return $this->requestOptions;
    }

    public function setRequestOptions(?array $requestOptions): static
    {
        $this->requestOptions = $requestOptions;

        return $this;
    }

    public function setCreateTime(?\DateTimeInterface $createdAt): self
    {
        $this->createTime = $createdAt;

        return $this;
    }

    public function getCreateTime(): ?\DateTimeInterface
    {
        return $this->createTime;
    }

    public function setCreatedBy(?string $createdBy): void
    {
        $this->createdBy = $createdBy;
    }

    public function getCreatedBy(): ?string
    {
        return $this->createdBy;
    }

    public function getCreatedFromIp(): ?string
    {
        return $this->createdFromIp;
    }

    public function setCreatedFromIp(?string $createdFromIp): void
    {
        $this->createdFromIp = $createdFromIp;
    }

    public function getCreatedFromUa(): ?string
    {
        return $this->createdFromUa;
    }

    public function setCreatedFromUa(?string $createdFromUa): static
    {
        $this->createdFromUa = $createdFromUa;

        return $this;
    }
}
