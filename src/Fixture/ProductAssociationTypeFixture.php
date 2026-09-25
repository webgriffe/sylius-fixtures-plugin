<?php

declare(strict_types=1);

namespace Webgriffe\SyliusFixturesPlugin\Fixture;

use Sylius\Bundle\CoreBundle\Fixture\ProductAssociationTypeFixture as BaseProductAssociationTypeFixture;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * Allows per locale translations of the name of a product association type.
 *
 * @see \Webgriffe\SyliusFixturesPlugin\Fixture\Factory\ProductAssociationTypeExampleFactory
 */
final class ProductAssociationTypeFixture extends BaseProductAssociationTypeFixture
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
