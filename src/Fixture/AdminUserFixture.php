<?php

declare(strict_types=1);

namespace Webgriffe\SyliusFixturesPlugin\Fixture;

use Sylius\Bundle\CoreBundle\Fixture\AdminUserFixture as BaseAdminUserFixture;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * Allows an administrator without access to the administration panel, such as a user of the API only.
 *
 * @see \Webgriffe\SyliusFixturesPlugin\Fixture\Factory\AdminUserExampleFactory
 */
final class AdminUserFixture extends BaseAdminUserFixture
{
    #[\Override]
    protected function configureResourceNode(ArrayNodeDefinition $resourceNode): void
    {
        parent::configureResourceNode($resourceNode);

        $resourceNode
            ->children()
                ->booleanNode('administration_access')->end()
            ->end()
        ;
    }
}
