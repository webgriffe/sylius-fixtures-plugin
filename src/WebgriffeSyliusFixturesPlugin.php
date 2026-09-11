<?php

declare(strict_types=1);

namespace Webgriffe\SyliusFixturesPlugin;

use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Webgriffe\SyliusFixturesPlugin\DependencyInjection\Compiler\OverrideSyliusFixturesPass;

final class WebgriffeSyliusFixturesPlugin extends Bundle
{
    use SyliusPluginTrait;

    #[\Override]
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    #[\Override]
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new OverrideSyliusFixturesPass());
    }
}
