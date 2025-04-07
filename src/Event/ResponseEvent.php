<?php

namespace HttpClientBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class ResponseEvent extends Event
{
    use RequestTrait;

    private int|float $duration;

    private int $statusCode;

    public function getDuration(): float|int
    {
        return $this->duration;
    }

    public function setDuration(float|int $duration): void
    {
        $this->duration = $duration;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setStatusCode(int $statusCode): void
    {
        $this->statusCode = $statusCode;
    }
}
