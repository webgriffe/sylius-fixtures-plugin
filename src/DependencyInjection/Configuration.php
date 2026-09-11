<?php

declare(strict_types=1);

namespace Webgriffe\SyliusFixturesPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    /**
     * @psalm-suppress UnusedVariable
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('webgriffe_sylius_fixtures');
        $rootNode = $treeBuilder->getRootNode();

        return $treeBuilder;
    }
}
