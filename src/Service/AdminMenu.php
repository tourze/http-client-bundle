<?php

declare(strict_types=1);

namespace HttpClientBundle\Service;

use HttpClientBundle\Entity\HttpRequestLog;
use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;

#[Autoconfigure(public: true)]
readonly class AdminMenu implements MenuProviderInterface
{
    public function __construct(private LinkGeneratorInterface $linkGenerator)
    {
    }

    public function __invoke(ItemInterface $item): void
    {
        $systemManagement = $item->getChild('系统管理');
        if (null === $systemManagement) {
            $systemManagement = $item->addChild('系统管理');
        }

        $systemManagement
            ->addChild('HTTP请求日志')
            ->setUri($this->linkGenerator->getCurdListPage(HttpRequestLog::class))
            ->setAttribute('icon', 'fas fa-globe')
        ;
    }
}
