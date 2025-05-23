<?php

namespace HttpClientBundle;

use HttpClientBundle\DependencyInjection\Compiler\RemoveUnusedServicePass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\BundleDependency\BundleDependencyInterface;

class HttpClientBundle extends Bundle implements BundleDependencyInterface
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new RemoveUnusedServicePass());
    }

    public static function getBundleDependencies(): array
    {
        return [
            \Tourze\Symfony\RuntimeContextBundle\RuntimeContextBundle::class => ['all' => true],
        ];
    }
}
