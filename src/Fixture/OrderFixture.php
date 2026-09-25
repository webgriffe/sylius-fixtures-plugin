<?php

declare(strict_types=1);

namespace Webgriffe\SyliusFixturesPlugin\Fixture;

use Sylius\Bundle\CoreBundle\Fixture\OrderFixture as BaseOrderFixture;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * Allows the state of the generated orders, so a demo store can show cancelled orders as well.
 *
 * @see \Webgriffe\SyliusFixturesPlugin\Fixture\Factory\OrderExampleFactory
 */
final class OrderFixture extends BaseOrderFixture
{
    #[\Override]
    protected function configureOptionsNode(ArrayNodeDefinition $optionsNode): void
    {
        parent::configureOptionsNode($optionsNode);

        $optionsNode
            ->children()
                ->enumNode('state')->values(['completed', 'cancelled'])->defaultValue('completed')->end()
            ->end()
        ;
    }
}
