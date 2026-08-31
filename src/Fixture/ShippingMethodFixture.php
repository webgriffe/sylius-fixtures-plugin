<?php

declare(strict_types=1);

namespace Webgriffe\SyliusFixturesPlugin\Fixture;

use Sylius\Bundle\CoreBundle\Fixture\ShippingMethodFixture as BaseShippingMethodFixture;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * Allows the category requirement in the shipping methods fixtures configuration.
 *
 * @see \Webgriffe\SyliusFixturesPlugin\Fixture\Factory\ShippingMethodExampleFactory
 */
final class ShippingMethodFixture extends BaseShippingMethodFixture
{
    #[\Override]
    protected function configureResourceNode(ArrayNodeDefinition $resourceNode): void
    {
        parent::configureResourceNode($resourceNode);

        $resourceNode
            ->children()
                ->enumNode('category_requirement')->values(['match_none', 'match_any', 'match_all'])->end()
            ->end()
        ;
    }
}
