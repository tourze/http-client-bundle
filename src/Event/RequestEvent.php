<?php

namespace HttpClientBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class RequestEvent extends Event
{
    use RequestTrait;
}
