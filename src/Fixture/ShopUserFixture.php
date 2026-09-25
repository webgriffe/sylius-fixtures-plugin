<?php

declare(strict_types=1);

namespace Webgriffe\SyliusFixturesPlugin\Fixture;

use Sylius\Bundle\CoreBundle\Fixture\ShopUserFixture as BaseShopUserFixture;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * Allows the newsletter subscription of a customer, which the Sylius fixtures never set.
 *
 * @see \Webgriffe\SyliusFixturesPlugin\Fixture\Factory\ShopUserExampleFactory
 */
final class ShopUserFixture extends BaseShopUserFixture
{
    #[\Override]
    protected function configureResourceNode(ArrayNodeDefinition $resourceNode): void
    {
        parent::configureResourceNode($resourceNode);

        $resourceNode
            ->children()
                ->booleanNode('subscribed_to_newsletter')->end()
            ->end()
        ;
    }
}
