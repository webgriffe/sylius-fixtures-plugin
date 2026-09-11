<?php

declare(strict_types=1);

namespace Webgriffe\SyliusFixturesPlugin\Fixture;

use Sylius\Bundle\CoreBundle\Fixture\TaxonFixture as BaseTaxonFixture;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * Allows images in the taxon fixtures configuration, at any level of the tree.
 *
 * @see \Webgriffe\SyliusFixturesPlugin\Fixture\Factory\TaxonExampleFactory
 */
final class TaxonFixture extends BaseTaxonFixture
{
    #[\Override]
    protected function configureResourceNode(ArrayNodeDefinition $resourceNode): void
    {
        parent::configureResourceNode($resourceNode);

        $resourceNode
            ->children()
                ->arrayNode('images')->variablePrototype()->end()->end()
            ->end()
        ;
    }
}
