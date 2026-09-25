<?php

declare(strict_types=1);

namespace Webgriffe\SyliusFixturesPlugin\Fixture;

use Sylius\Bundle\CoreBundle\Fixture\PromotionFixture as BasePromotionFixture;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * Allows per locale translations of the label of a cart promotion, the text the customer reads on the cart.
 *
 * @see \Webgriffe\SyliusFixturesPlugin\Fixture\Factory\PromotionExampleFactory
 */
final class PromotionFixture extends BasePromotionFixture
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
