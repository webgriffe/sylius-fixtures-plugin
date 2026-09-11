<?php

declare(strict_types=1);

namespace Webgriffe\SyliusFixturesPlugin\Fixture\Factory;

use Sylius\Bundle\CoreBundle\Fixture\Factory\ExampleFactoryInterface;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Webmozart\Assert\Assert;

/**
 * Translates the name of a product option and its values, which Sylius writes identically in every locale.
 *
 * A value is either a string, translated in every locale as Sylius does, or a map of locale to value:
 *
 *     values:
 *         two_glasses:
 *             it_IT: '2 calici'
 *             en_US: '2 glasses'
 *
 * @implements ExampleFactoryInterface<ProductOptionInterface>
 */
final readonly class ProductOptionExampleFactory implements ExampleFactoryInterface
{
    /** @param ExampleFactoryInterface<ProductOptionInterface> $decoratedFactory */
    public function __construct(
        private ExampleFactoryInterface $decoratedFactory,
    ) {
    }

    /** @param array<string, mixed> $options */
    #[\Override]
    public function create(array $options = []): ProductOptionInterface
    {
        /** @var array<string, string> $nameTranslations */
        $nameTranslations = $options['translations'] ?? [];
        unset($options['translations']);

        /** @var array<string, mixed> $configuredValues */
        $configuredValues = $options['values'] ?? [];
        $options['values'] = array_map($this->defaultValueOf(...), $configuredValues);

        $productOption = $this->decoratedFactory->create($options);

        $this->translate($productOption, $nameTranslations);

        foreach ($productOption->getValues() as $productOptionValue) {
            $this->translateValue($productOptionValue, $configuredValues[$productOptionValue->getCode()] ?? null);
        }

        return $productOption;
    }

    /**
     * The Sylius factory only takes a string, so the value of the first locale is the one it writes everywhere
     * before the translations below replace it.
     */
    private function defaultValueOf(mixed $value): string
    {
        if (is_array($value)) {
            $value = reset($value);
        }

        Assert::string($value);

        return $value;
    }

    /** @param array<string, string> $translations */
    private function translate(ProductOptionInterface $productOption, array $translations): void
    {
        foreach ($translations as $localeCode => $name) {
            $productOption->setCurrentLocale($localeCode);
            // the fallback locale must match the current one, otherwise the fallback translation is overwritten
            $productOption->setFallbackLocale($localeCode);

            $productOption->setName($name);
        }
    }

    private function translateValue(ProductOptionValueInterface $productOptionValue, mixed $value): void
    {
        if (!is_array($value)) {
            return;
        }

        /** @var array<string, string> $translations */
        $translations = $value;

        foreach ($translations as $localeCode => $translation) {
            $productOptionValue->setCurrentLocale($localeCode);
            $productOptionValue->setFallbackLocale($localeCode);

            $productOptionValue->setValue($translation);
        }
    }
}
