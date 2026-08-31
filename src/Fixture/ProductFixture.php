<?php

declare(strict_types=1);

namespace Webgriffe\SyliusFixturesPlugin\Fixture;

use Sylius\Bundle\CoreBundle\Fixture\ProductFixture as BaseProductFixture;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * Allows fixed prices (per product or per product option value), per locale translations and stock
 * in the product fixtures configuration.
 *
 * @see \Webgriffe\SyliusFixturesPlugin\Fixture\Factory\ProductExampleFactory
 */
final class ProductFixture extends BaseProductFixture
{
    #[\Override]
    protected function configureResourceNode(ArrayNodeDefinition $resourceNode): void
    {
        parent::configureResourceNode($resourceNode);

        $resourceNode
            ->children()
                ->floatNode('price')->end()
                ->arrayNode('variant_prices')
                    ->useAttributeAsKey('option_value')
                    ->floatPrototype()->end()
                ->end()
                ->floatNode('original_price')->end()
                ->integerNode('on_hand')->end()
                ->scalarNode('shipping_category')->cannotBeEmpty()->end()
                ->arrayNode('translations')
                    ->useAttributeAsKey('locale')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('name')->cannotBeEmpty()->end()
                            ->scalarNode('slug')->cannotBeEmpty()->end()
                            ->scalarNode('short_description')->cannotBeEmpty()->end()
                            ->scalarNode('description')->cannotBeEmpty()->end()
                        ->end()
                    ->end()
                ->end()
        ;
    }
}
