<?php

declare(strict_types=1);

namespace Webgriffe\SyliusFixturesPlugin\Fixture\Factory;

use Sylius\Bundle\CoreBundle\Fixture\Factory\ExampleFactoryInterface;
use Sylius\Component\Core\Model\AdminUserInterface;

/**
 * Lets an administrator go without access to the administration panel, which the Sylius factory always grants:
 * a user of the API only gets the API access level alone.
 *
 * @implements ExampleFactoryInterface<AdminUserInterface>
 */
final readonly class AdminUserExampleFactory implements ExampleFactoryInterface
{
    /** @param ExampleFactoryInterface<AdminUserInterface> $decoratedFactory */
    public function __construct(
        private ExampleFactoryInterface $decoratedFactory,
    ) {
    }

    /** @param array<string, mixed> $options */
    #[\Override]
    public function create(array $options = []): AdminUserInterface
    {
        $administrationAccess = $options['administration_access'] ?? null;
        unset($options['administration_access']);

        $adminUser = $this->decoratedFactory->create($options);

        if (is_bool($administrationAccess)) {
            $adminUser->setAdministrationAccess($administrationAccess);
        }

        return $adminUser;
    }
}
