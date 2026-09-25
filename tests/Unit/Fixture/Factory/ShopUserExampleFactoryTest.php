<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusFixturesPlugin\Unit\Fixture\Factory;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\CoreBundle\Fixture\Factory\ExampleFactoryInterface;
use Sylius\Component\Core\Model\Customer;
use Sylius\Component\Core\Model\ShopUser;
use Sylius\Component\Core\Model\ShopUserInterface;
use Webgriffe\SyliusFixturesPlugin\Fixture\Factory\ShopUserExampleFactory;

#[CoversClass(ShopUserExampleFactory::class)]
final class ShopUserExampleFactoryTest extends TestCase
{
    public function testItSubscribesTheCustomerToTheNewsletter(): void
    {
        $user = $this->createUser();

        $this->createFactory($user)->create(['email' => 'mario@example.com', 'subscribed_to_newsletter' => true]);

        self::assertTrue($user->getCustomer()?->isSubscribedToNewsletter());
    }

    public function testItLeavesTheCustomerOutOfTheNewsletterByDefault(): void
    {
        $user = $this->createUser();

        $this->createFactory($user)->create(['email' => 'mario@example.com']);

        self::assertFalse($user->getCustomer()?->isSubscribedToNewsletter());
    }

    public function testItDoesNotForwardTheSubscriptionToTheDecoratedFactory(): void
    {
        $user = $this->createUser();

        $decoratedFactory = $this->createMock(ExampleFactoryInterface::class);
        $decoratedFactory
            ->expects(self::once())
            ->method('create')
            ->with(['email' => 'mario@example.com'])
            ->willReturn($user)
        ;

        (new ShopUserExampleFactory($decoratedFactory))->create([
            'email' => 'mario@example.com',
            'subscribed_to_newsletter' => true,
        ]);
    }

    private function createUser(): ShopUser
    {
        $user = new ShopUser();
        $user->setCustomer(new Customer());

        return $user;
    }

    private function createFactory(ShopUserInterface $user): ShopUserExampleFactory
    {
        $decoratedFactory = $this->createMock(ExampleFactoryInterface::class);
        $decoratedFactory->method('create')->willReturn($user);

        return new ShopUserExampleFactory($decoratedFactory);
    }
}
