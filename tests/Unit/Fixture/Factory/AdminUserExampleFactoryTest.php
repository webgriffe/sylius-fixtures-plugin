<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusFixturesPlugin\Unit\Fixture\Factory;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\CoreBundle\Fixture\Factory\ExampleFactoryInterface;
use Sylius\Component\Core\Model\AdminUser;
use Sylius\Component\Core\Model\AdminUserInterface;
use Webgriffe\SyliusFixturesPlugin\Fixture\Factory\AdminUserExampleFactory;

#[CoversClass(AdminUserExampleFactory::class)]
final class AdminUserExampleFactoryTest extends TestCase
{
    public function testItRevokesTheAdministrationAccessOfAnApiUser(): void
    {
        $adminUser = $this->createApiUser();

        $decoratedFactory = $this->createMock(ExampleFactoryInterface::class);
        $decoratedFactory
            ->expects(self::once())
            ->method('create')
            ->with(['username' => 'api', 'api' => true])
            ->willReturn($adminUser)
        ;

        (new AdminUserExampleFactory($decoratedFactory))->create([
            'username' => 'api',
            'api' => true,
            'administration_access' => false,
        ]);

        self::assertFalse($adminUser->hasAdministrationAccess());
        self::assertTrue($adminUser->hasApiAccess());
    }

    public function testItKeepsTheAdministrationAccessWhenTheOptionIsMissing(): void
    {
        $adminUser = $this->createApiUser();

        $decoratedFactory = $this->createMock(ExampleFactoryInterface::class);
        $decoratedFactory->method('create')->willReturn($adminUser);

        (new AdminUserExampleFactory($decoratedFactory))->create(['username' => 'api', 'api' => true]);

        self::assertTrue($adminUser->hasAdministrationAccess());
    }

    /** The user as the Sylius factory creates it: the administration access is always granted. */
    private function createApiUser(): AdminUserInterface
    {
        $adminUser = new AdminUser();
        $adminUser->setAdministrationAccess(true);
        $adminUser->setApiAccess(true);

        return $adminUser;
    }
}
