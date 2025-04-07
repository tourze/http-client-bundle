<?php

namespace HttpClientBundle\EventSubscriber;

use HttpClientBundle\Event\RequestEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * 有时候开发会搞混乱本地开发时的请求次序，为此特地加一个检测
 */
class CheckLocalDomainSubscriber
{
    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    #[AsEventListener]
    public function checkLocalDomains(RequestEvent $event): void
    {
        if ('prod' === $_ENV['APP_ENV']) {
            return;
        }
        $request = $this->requestStack->getMainRequest();
        if (null === $request) {
            return;
        }

        $url = $event->getUrl();
        $currentHost = $request->getSchemeAndHttpHost();
        if ($currentHost === $url || str_starts_with($url, $currentHost)) {
            throw new \LogicException("在开发阶段，为了避免HTTP请求时可能造成的进程阻塞，请不要使用 HttpClient 请求 {$currentHost} 相关地址。");
        }
    }
}
