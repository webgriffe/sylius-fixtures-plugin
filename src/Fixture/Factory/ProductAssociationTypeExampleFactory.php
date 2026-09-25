<?php

declare(strict_types=1);

namespace Webgriffe\SyliusFixturesPlugin\Fixture\Factory;

use Sylius\Bundle\CoreBundle\Fixture\Factory\ExampleFactoryInterface;
use Sylius\Component\Product\Model\ProductAssociationTypeInterface;

/**
 * Translates the name of a product association type, which Sylius writes identically in every locale.
 *
 * @implements ExampleFactoryInterface<ProductAssociationTypeInterface>
 */
final readonly class ProductAssociationTypeExampleFactory implements ExampleFactoryInterface
{
    /** @param ExampleFactoryInterface<ProductAssociationTypeInterface> $decoratedFactory */
    public function __construct(
        private ExampleFactoryInterface $decoratedFactory,
    ) {
    }

    /** @param array<string, mixed> $options */
    #[\Override]
    public function create(array $options = []): ProductAssociationTypeInterface
    {
        /** @var array<string, string> $translations */
        $translations = $options['translations'] ?? [];
        unset($options['translations']);

        $associationType = $this->decoratedFactory->create($options);

        foreach ($translations as $localeCode => $name) {
            $associationType->setCurrentLocale($localeCode);
            // the fallback locale must match the current one, otherwise the fallback translation is overwritten
            $associationType->setFallbackLocale($localeCode);

            $associationType->setName($name);
        }

        return $associationType;
    }
}
