<?php

declare(strict_types=1);

namespace Webgriffe\SyliusFixturesPlugin\Fixture;

use Sylius\Bundle\CoreBundle\Fixture\ProductFixture as BaseProductFixture;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * Allows fixed prices, minimum prices, dimensions, stock, shipping category and complete per locale
 * translations (meta included) in the product fixtures configuration.
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
                ->floatNode('minimum_price')->end()
                ->floatNode('minimum_price_ratio')->end()
                ->floatNode('original_price')->end()
                ->floatNode('width')->end()
                ->floatNode('height')->end()
                ->floatNode('depth')->end()
                ->floatNode('weight')->end()
                ->arrayNode('variant_dimensions')
                    ->useAttributeAsKey('option_values')
                    ->arrayPrototype()
                        ->children()
                            ->floatNode('width')->end()
                            ->floatNode('height')->end()
                            ->floatNode('depth')->end()
                            ->floatNode('weight')->end()
                        ->end()
                    ->end()
                ->end()
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
                            ->scalarNode('meta_keywords')->cannotBeEmpty()->end()
                            ->scalarNode('meta_description')->cannotBeEmpty()->end()
                        ->end()
                    ->end()
                ->end()
        ;
    }
}
