<?php

namespace HttpClientBundle\Tests\DependencyInjection\Compiler;

use HttpClientBundle\Request\ApiRequest;

class TestApiRequest extends ApiRequest
{
    public function getUrl(): string
    {
        return 'https://example.com/test';
    }

    public function getRequestPath(): string
    {
        return '/test';
    }

    public function getRequestOptions(): ?array
    {
        return [];
    }
}
