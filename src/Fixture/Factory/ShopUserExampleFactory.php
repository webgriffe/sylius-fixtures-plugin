<?php

declare(strict_types=1);

namespace Webgriffe\SyliusFixturesPlugin\Fixture\Factory;

use Sylius\Bundle\CoreBundle\Fixture\Factory\ExampleFactoryInterface;
use Sylius\Component\Core\Model\ShopUserInterface;

/**
 * Subscribes a customer to the newsletter, which the Sylius factory leaves to its default value.
 *
 * @implements ExampleFactoryInterface<ShopUserInterface>
 */
final readonly class ShopUserExampleFactory implements ExampleFactoryInterface
{
    /** @param ExampleFactoryInterface<ShopUserInterface> $decoratedFactory */
    public function __construct(
        private ExampleFactoryInterface $decoratedFactory,
    ) {
    }

    /** @param array<string, mixed> $options */
    #[\Override]
    public function create(array $options = []): ShopUserInterface
    {
        $subscribed = $options['subscribed_to_newsletter'] ?? false;
        unset($options['subscribed_to_newsletter']);

        $user = $this->decoratedFactory->create($options);

        $user->getCustomer()?->setSubscribedToNewsletter((bool) $subscribed);

        return $user;
    }
}
