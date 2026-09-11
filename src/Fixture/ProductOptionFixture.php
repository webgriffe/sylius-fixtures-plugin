<?php

declare(strict_types=1);

namespace Webgriffe\SyliusFixturesPlugin\Fixture;

use Sylius\Bundle\CoreBundle\Fixture\ProductOptionFixture as BaseProductOptionFixture;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * Allows per locale translations of the name of a product option and of its values.
 *
 * @see \Webgriffe\SyliusFixturesPlugin\Fixture\Factory\ProductOptionExampleFactory
 */
final class ProductOptionFixture extends BaseProductOptionFixture
{
    #[\Override]
    protected function configureResourceNode(ArrayNodeDefinition $resourceNode): void
    {
        $resourceNode
            ->children()
                ->scalarNode('name')->cannotBeEmpty()->end()
                ->scalarNode('code')->cannotBeEmpty()->end()
                ->arrayNode('translations')
                    ->useAttributeAsKey('locale')
                    ->scalarPrototype()->end()
                ->end()
                ->arrayNode('values')
                    ->requiresAtLeastOneElement()
                    ->useAttributeAsKey('code')
                    ->variablePrototype()->end()
                ->end()
            ->end()
        ;
    }
}
