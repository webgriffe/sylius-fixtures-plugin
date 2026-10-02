<?php

declare(strict_types=1);

namespace Webgriffe\SyliusFixturesPlugin\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Webgriffe\SyliusFixturesPlugin\Fixture\AdminUserFixture;
use Webgriffe\SyliusFixturesPlugin\Fixture\CatalogPromotionFixture;
use Webgriffe\SyliusFixturesPlugin\Fixture\Factory\OrderExampleFactory;
use Webgriffe\SyliusFixturesPlugin\Fixture\OrderFixture;
use Webgriffe\SyliusFixturesPlugin\Fixture\PaymentMethodFixture;
use Webgriffe\SyliusFixturesPlugin\Fixture\ProductAssociationTypeFixture;
use Webgriffe\SyliusFixturesPlugin\Fixture\ProductFixture;
use Webgriffe\SyliusFixturesPlugin\Fixture\PromotionFixture;
use Webgriffe\SyliusFixturesPlugin\Fixture\ShippingMethodFixture;
use Webgriffe\SyliusFixturesPlugin\Fixture\ShopUserFixture;
use Webgriffe\SyliusFixturesPlugin\Fixture\TaxonFixture;

/**
 * Replaces the class of some Sylius fixtures services, keeping their arguments: the fixtures of this plugin
 * only add configuration options, so they do not need a definition of their own.
 */
final class OverrideSyliusFixturesPass implements CompilerPassInterface
{
    private const OVERRIDDEN_CLASSES = [
        'sylius.fixture.admin_user' => AdminUserFixture::class,
        'sylius.fixture.catalog_promotion' => CatalogPromotionFixture::class,
        'sylius.fixture.order' => OrderFixture::class,
        'sylius.fixture.product' => ProductFixture::class,
        'sylius.fixture.payment_method' => PaymentMethodFixture::class,
        'sylius.fixture.product_association_type' => ProductAssociationTypeFixture::class,
        'sylius.fixture.promotion' => PromotionFixture::class,
        'sylius.fixture.shipping_method' => ShippingMethodFixture::class,
        'sylius.fixture.shop_user' => ShopUserFixture::class,
        'sylius.fixture.taxon' => TaxonFixture::class,
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

        $this->injectShippingMethodsResolver($container);
    }

    /** The order factory ships the demo orders with an eligible method, see {@see OrderExampleFactory}. */
    private function injectShippingMethodsResolver(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('sylius.fixture.example_factory.order')) {
            return;
        }

        $container
            ->getDefinition('sylius.fixture.example_factory.order')
            ->addMethodCall('setShippingMethodsResolver', [new Reference('sylius.resolver.shipping_methods')])
        ;
    }
}
