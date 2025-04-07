<?php

namespace HttpClientBundle\Request;

interface LockRequest
{
    /**
     * 锁
     */
    public function getLockKey(): string;
}
