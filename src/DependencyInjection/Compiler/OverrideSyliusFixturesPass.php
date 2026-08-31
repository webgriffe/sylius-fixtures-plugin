<?php

declare(strict_types=1);

namespace Webgriffe\SyliusFixturesPlugin\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Webgriffe\SyliusFixturesPlugin\Fixture\Factory\OrderExampleFactory;
use Webgriffe\SyliusFixturesPlugin\Fixture\ProductFixture;
use Webgriffe\SyliusFixturesPlugin\Fixture\ShippingMethodFixture;

/**
 * Replaces the class of some Sylius fixtures services, keeping their arguments: the fixtures of this plugin
 * only add configuration options, so they do not need a definition of their own.
 */
final class OverrideSyliusFixturesPass implements CompilerPassInterface
{
    private const OVERRIDDEN_CLASSES = [
        'sylius.fixture.product' => ProductFixture::class,
        'sylius.fixture.shipping_method' => ShippingMethodFixture::class,
        'sylius.fixture.example_factory.order' => OrderExampleFactory::class,
    ];

    public function process(ContainerBuilder $container): void
    {
        foreach (self::OVERRIDDEN_CLASSES as $serviceId => $class) {
            if (!$container->hasDefinition($serviceId)) {
                continue;
            }

            $container->getDefinition($serviceId)->setClass($class);
        }
    }
}
