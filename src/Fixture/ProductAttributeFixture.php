<?php

declare(strict_types=1);

namespace Webgriffe\SyliusFixturesPlugin\Fixture;

use Sylius\Bundle\CoreBundle\Fixture\ProductAttributeFixture as BaseProductAttributeFixture;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * Allows per locale translations of the name of a product attribute.
 *
 * @see \Webgriffe\SyliusFixturesPlugin\Fixture\Factory\ProductAttributeExampleFactory
 */
final class ProductAttributeFixture extends BaseProductAttributeFixture
{
    #[\Override]
    protected function configureResourceNode(ArrayNodeDefinition $resourceNode): void
    {
        parent::configureResourceNode($resourceNode);

        $resourceNode
            ->children()
                ->arrayNode('translations')
                    ->useAttributeAsKey('locale')
                    ->scalarPrototype()->end()
                ->end()
            ->end()
        ;
    }
}
