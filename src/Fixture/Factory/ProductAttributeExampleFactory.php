<?php

declare(strict_types=1);

namespace Webgriffe\SyliusFixturesPlugin\Fixture\Factory;

use Sylius\Bundle\CoreBundle\Fixture\Factory\ExampleFactoryInterface;
use Sylius\Component\Product\Model\ProductAttributeInterface;

/**
 * Translates the name of a product attribute, which Sylius writes identically in every locale.
 *
 * @implements ExampleFactoryInterface<ProductAttributeInterface>
 */
final readonly class ProductAttributeExampleFactory implements ExampleFactoryInterface
{
    /** @param ExampleFactoryInterface<ProductAttributeInterface> $decoratedFactory */
    public function __construct(
        private ExampleFactoryInterface $decoratedFactory,
    ) {
    }

    /** @param array<string, mixed> $options */
    #[\Override]
    public function create(array $options = []): ProductAttributeInterface
    {
        /** @var array<string, string> $translations */
        $translations = $options['translations'] ?? [];
        unset($options['translations']);

        $productAttribute = $this->decoratedFactory->create($options);

        foreach ($translations as $localeCode => $name) {
            $productAttribute->setCurrentLocale($localeCode);
            // the fallback locale must match the current one, otherwise the fallback translation is overwritten
            $productAttribute->setFallbackLocale($localeCode);

            $productAttribute->setName($name);
        }

        return $productAttribute;
    }
}
