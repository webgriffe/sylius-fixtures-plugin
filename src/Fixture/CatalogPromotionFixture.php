<?php

declare(strict_types=1);

namespace Webgriffe\SyliusFixturesPlugin\Fixture;

use Sylius\Bundle\CoreBundle\Fixture\CatalogPromotionFixture as BaseCatalogPromotionFixture;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * Allows per locale translations of the label and the description of a catalog promotion.
 *
 * @see \Webgriffe\SyliusFixturesPlugin\Fixture\Factory\CatalogPromotionExampleFactory
 */
final class CatalogPromotionFixture extends BaseCatalogPromotionFixture
{
    #[\Override]
    protected function configureResourceNode(ArrayNodeDefinition $resourceNode): void
    {
        parent::configureResourceNode($resourceNode);

        $resourceNode
            ->children()
                ->arrayNode('translations')
                    ->useAttributeAsKey('locale')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('label')->cannotBeEmpty()->end()
                            ->scalarNode('description')->cannotBeEmpty()->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
