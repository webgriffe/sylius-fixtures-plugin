<?php

declare(strict_types=1);

namespace Webgriffe\SyliusFixturesPlugin\Fixture;

use Sylius\Bundle\CoreBundle\Fixture\PromotionFixture as BasePromotionFixture;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * Allows per locale translations of the label of a cart promotion, the text the customer reads on the cart, and
 * whether the promotion and its coupons track their usage.
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
                ->booleanNode('track_usage')->end()
                // replaces the node of Sylius, whose prototype cannot be extended: its options are kept as they are
                ->arrayNode('coupons')->arrayPrototype()
                    ->children()
                        ->scalarNode('code')->cannotBeEmpty()->end()
                        ->scalarNode('expires_at')->defaultNull()->end()
                        ->integerNode('per_customer_usage_limit')->defaultNull()->end()
                        ->booleanNode('reusable_from_cancelled_orders')->defaultTrue()->end()
                        ->integerNode('usage_limit')->defaultNull()->end()
                        ->booleanNode('track_usage')->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
