<?php

namespace HttpClientBundle\Exception;

use HttpClientBundle\Request\RequestInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Tourze\BacktraceHelper\ContextAwareInterface;

/**
 * 外部接口报错
 */
class HttpClientException extends \Exception implements ContextAwareInterface
{
    public function __construct(RequestInterface $request, ResponseInterface $response, string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->setContext([
            ...static::extractResponse($response),
            'request' => $request,
        ]);
    }

    private array $context = [];

    public function getContext(): array
    {
        return $this->context;
    }

    public function setContext(array $context): void
    {
        $this->context = $context;
    }

    public static function extractResponse(ResponseInterface $response): array
    {
        return [
            'content' => $response->getContent(false),
            'info' => $response->getInfo(),
        ];
    }
}
