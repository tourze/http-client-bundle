<?php

namespace HttpClientBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class RequestEvent extends Event
{
    use RequestTrait;
}
