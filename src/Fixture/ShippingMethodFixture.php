<?php

declare(strict_types=1);

namespace Webgriffe\SyliusFixturesPlugin\Fixture;

use Sylius\Bundle\CoreBundle\Fixture\ShippingMethodFixture as BaseShippingMethodFixture;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * Allows the category requirement, the estimated delivery time, the rules and the per locale translations
 * in the shipping methods fixtures configuration.
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
                ->integerNode('min_delivery_time_days')->min(0)->end()
                ->integerNode('max_delivery_time_days')->min(0)->end()
                ->arrayNode('rules')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('type')->isRequired()->cannotBeEmpty()->end()
                            ->variableNode('configuration')->defaultValue([])->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('translations')
                    ->useAttributeAsKey('locale')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('name')->cannotBeEmpty()->end()
                            ->scalarNode('description')->cannotBeEmpty()->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
