<?php

declare(strict_types=1);

namespace Webgriffe\SyliusFixturesPlugin\Fixture\Factory;

use Sylius\Bundle\CoreBundle\Fixture\Factory\ExampleFactoryInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;

/**
 * Translates the name, the description and the instructions of a payment method, which Sylius writes
 * identically in every locale.
 *
 * @implements ExampleFactoryInterface<PaymentMethodInterface>
 */
final readonly class PaymentMethodExampleFactory implements ExampleFactoryInterface
{
    /** @param ExampleFactoryInterface<PaymentMethodInterface> $decoratedFactory */
    public function __construct(
        private ExampleFactoryInterface $decoratedFactory,
    ) {
    }

    /** @param array<string, mixed> $options */
    #[\Override]
    public function create(array $options = []): PaymentMethodInterface
    {
        /** @var array<string, array<string, string>> $translations */
        $translations = $options['translations'] ?? [];
        unset($options['translations']);

        $paymentMethod = $this->decoratedFactory->create($options);

        foreach ($translations as $localeCode => $translation) {
            $paymentMethod->setCurrentLocale($localeCode);
            // the fallback locale must match the current one, otherwise the fallback translation is overwritten
            $paymentMethod->setFallbackLocale($localeCode);

            if (isset($translation['name'])) {
                $paymentMethod->setName($translation['name']);
            }
            if (isset($translation['description'])) {
                $paymentMethod->setDescription($translation['description']);
            }
            if (isset($translation['instructions'])) {
                $paymentMethod->setInstructions($translation['instructions']);
            }
        }

        return $paymentMethod;
    }
}
