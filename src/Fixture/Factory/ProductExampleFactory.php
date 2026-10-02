<?php

declare(strict_types=1);

namespace Webgriffe\SyliusFixturesPlugin\Fixture\Factory;

use Sylius\Bundle\CoreBundle\Fixture\Factory\ExampleFactoryInterface;
use Sylius\Component\Core\Model\ChannelPricingInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Product\Generator\SlugGeneratorInterface;
use Sylius\Component\Shipping\Model\ShippingCategoryInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Webmozart\Assert\Assert;

/**
 * Adds fixed prices, per locale translations and stock to the Sylius product example factory,
 * which otherwise generates random prices and the same translation for every locale.
 *
 * Configurable products can price each variant through "variant_prices", keyed by product option value,
 * and size it through "variant_dimensions".
 * It also assigns the minimum price, the dimensions and the shipping category, which the Sylius factory
 * never sets on the variants.
 *
 * @implements ExampleFactoryInterface<ProductInterface>
 */
final readonly class ProductExampleFactory implements ExampleFactoryInterface
{
    private const EXTRA_OPTIONS = [
        'price',
        'variant_prices',
        'minimum_price',
        'minimum_price_ratio',
        'original_price',
        'on_hand',
        'shipping_category',
        'width',
        'height',
        'depth',
        'weight',
        'variant_dimensions',
        'translations',
        'variant_name',
    ];

    private const DIMENSIONS = ['width', 'height', 'depth', 'weight'];

    /**
     * @param ExampleFactoryInterface<ProductInterface> $decoratedFactory
     * @param RepositoryInterface<ShippingCategoryInterface> $shippingCategoryRepository
     * @param RepositoryInterface<LocaleInterface> $localeRepository
     */
    public function __construct(
        private ExampleFactoryInterface $decoratedFactory,
        private RepositoryInterface $shippingCategoryRepository,
        private RepositoryInterface $localeRepository,
        private SlugGeneratorInterface $slugGenerator,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    #[\Override]
    public function create(array $options = []): ProductInterface
    {
        $extraOptions = array_intersect_key($options, array_flip(self::EXTRA_OPTIONS));

        /** @var array<string, mixed> $attributes */
        $attributes = $options['product_attributes'] ?? [];
        $options['product_attributes'] = array_map($this->defaultAttributeValueOf(...), $attributes);
        if ([] === $options['product_attributes']) {
            unset($options['product_attributes']);
        }

        $product = $this->decoratedFactory->create(array_diff_key($options, $extraOptions));

        $this->translateAttributeValues($product, $attributes);

        /** @var array<string, array<string, string>> $translations */
        $translations = $extraOptions['translations'] ?? [];
        $this->translate($product, $translations);

        foreach ($product->getVariants() as $variant) {
            Assert::isInstanceOf($variant, ProductVariantInterface::class);

            $this->applyStock($variant, $extraOptions);
            $this->applyPrices($variant, $extraOptions);
            $this->applyShippingCategory($variant, $extraOptions);
            $this->applyDimensions($variant, $extraOptions);
            $this->nameVariant($product, $variant, $extraOptions);
        }

        return $product;
    }

    /**
     * The Sylius factory writes the same value of an attribute in every locale, so the value of the first
     * locale is the one it writes everywhere before the translations below replace it.
     */
    private function defaultAttributeValueOf(mixed $value): mixed
    {
        if (!is_array($value) || [] === $value) {
            return $value;
        }

        return reset($value);
    }

    /**
     * An attribute value given as a map of locale to value is translated here, one attribute value per locale
     * as Sylius stores them.
     *
     * @param array<string, mixed> $attributes
     */
    private function translateAttributeValues(ProductInterface $product, array $attributes): void
    {
        foreach ($product->getAttributes() as $attributeValue) {
            $attribute = $attributeValue->getAttribute();
            $localeCode = $attributeValue->getLocaleCode();
            if (null === $attribute || null === $localeCode) {
                continue;
            }

            $translations = $attributes[(string) $attribute->getCode()] ?? null;
            if (!is_array($translations) || !isset($translations[$localeCode])) {
                continue;
            }

            $attributeValue->setValue($translations[$localeCode]);
        }
    }

    /** @param array<string, array<string, string>> $translations */
    private function translate(ProductInterface $product, array $translations): void
    {
        foreach ($translations as $localeCode => $translation) {
            $product->setCurrentLocale($localeCode);
            // The fallback locale must match the current one, otherwise the fallback translation is overwritten.
            $product->setFallbackLocale($localeCode);

            if (isset($translation['name'])) {
                $product->setName($translation['name']);
            }
            // Sylius generates the slug once, from the name of the first locale: a product translated in
            // several languages would carry the same slug everywhere.
            if (isset($translation['slug'])) {
                $product->setSlug($translation['slug']);
            } elseif (isset($translation['name'])) {
                $product->setSlug($this->slugGenerator->generate($translation['name']));
            }
            if (isset($translation['short_description'])) {
                $product->setShortDescription($translation['short_description']);
            }
            if (isset($translation['description'])) {
                $product->setDescription($translation['description']);
            }
            if (isset($translation['meta_keywords'])) {
                $product->setMetaKeywords($translation['meta_keywords']);
            }
            if (isset($translation['meta_description'])) {
                $product->setMetaDescription($translation['meta_description']);
            }
        }
    }

    /** @param array<string, mixed> $options */
    private function applyStock(ProductVariantInterface $variant, array $options): void
    {
        if (!isset($options['on_hand'])) {
            return;
        }

        $onHand = $options['on_hand'];
        Assert::integer($onHand);

        $variant->setOnHand($onHand);
    }

    /**
     * Sylius names a variant once, in whatever locale the entity happens to be in: after its option values, or
     * with an empty name when it has none. So every variant ends up with a single translation, which the
     * administration flags as missing in the other locales. A variant with option values is named after them,
     * a variant without any after "variant_name", or after the product when the fixture does not give one.
     *
     * @param array<string, mixed> $options
     */
    private function nameVariant(ProductInterface $product, ProductVariantInterface $variant, array $options): void
    {
        /** @var array<string, string> $variantNames */
        $variantNames = $options['variant_name'] ?? [];

        foreach ($this->localeRepository->findAll() as $locale) {
            $localeCode = $locale->getCode();
            Assert::string($localeCode);

            $name = $variant->getOptionValues()->isEmpty()
                ? $variantNames[$localeCode] ?? $this->productNameIn($product, $localeCode)
                : $this->optionValuesNameIn($variant, $localeCode);

            $variant->setCurrentLocale($localeCode);
            $variant->setFallbackLocale($localeCode);
            $variant->setName($name);
        }
    }

    private function optionValuesNameIn(ProductVariantInterface $variant, string $localeCode): string
    {
        $names = [];
        foreach ($variant->getOptionValues() as $optionValue) {
            $optionValue->setCurrentLocale($localeCode);
            $optionValue->setFallbackLocale($localeCode);

            $names[] = (string) $optionValue->getValue();
        }

        return implode(' ', $names);
    }

    private function productNameIn(ProductInterface $product, string $localeCode): ?string
    {
        $product->setCurrentLocale($localeCode);
        $product->setFallbackLocale($localeCode);

        return $product->getName();
    }

    /** @param array<string, mixed> $options */
    private function applyShippingCategory(ProductVariantInterface $variant, array $options): void
    {
        if (!isset($options['shipping_category'])) {
            return;
        }

        $code = $options['shipping_category'];
        Assert::string($code);

        $shippingCategory = $this->shippingCategoryRepository->findOneBy(['code' => $code]);
        Assert::isInstanceOf($shippingCategory, ShippingCategoryInterface::class, sprintf('Unknown shipping category "%s".', $code));

        $variant->setShippingCategory($shippingCategory);
    }

    /** @param array<string, mixed> $options */
    private function applyPrices(ProductVariantInterface $variant, array $options): void
    {
        $price = $this->priceOf($variant, $options);
        $minimumPrice = $this->minimumPriceOf($price, $options);

        /** @var ChannelPricingInterface $channelPricing */
        foreach ($variant->getChannelPricings() as $channelPricing) {
            if (null !== $price) {
                $channelPricing->setPrice($this->toMinorUnit($price));
            }
            if (isset($options['original_price'])) {
                $channelPricing->setOriginalPrice($this->toMinorUnit($options['original_price']));
            }
            if (null !== $minimumPrice) {
                $channelPricing->setMinimumPrice($minimumPrice);
            }
        }
    }

    /**
     * The minimum price is the floor the catalog promotions cannot go below. It is either given as is with
     * "minimum_price", or computed from the price of the variant with "minimum_price_ratio".
     *
     * @param array<string, mixed> $options
     */
    private function minimumPriceOf(mixed $price, array $options): ?int
    {
        if (isset($options['minimum_price'])) {
            return $this->toMinorUnit($options['minimum_price']);
        }

        if (!isset($options['minimum_price_ratio']) || null === $price) {
            return null;
        }

        $ratio = $options['minimum_price_ratio'];
        Assert::numeric($ratio);

        return (int) round($this->toMinorUnit($price) * (float) $ratio);
    }

    /** @param array<string, mixed> $options */
    private function applyDimensions(ProductVariantInterface $variant, array $options): void
    {
        /** @var array<array-key, mixed> $configuredDimensions */
        $configuredDimensions = $options['variant_dimensions'] ?? [];
        /** @var array<string, mixed> $variantDimensions */
        $variantDimensions = $this->matchOptionValues($configuredDimensions, $variant) ?? [];

        $dimensions = [];
        foreach (self::DIMENSIONS as $dimension) {
            $value = $variantDimensions[$dimension] ?? $options[$dimension] ?? null;
            if (null === $value) {
                continue;
            }

            Assert::numeric($value);
            $dimensions[$dimension] = (float) $value;
        }

        if (isset($dimensions['width'])) {
            $variant->setWidth($dimensions['width']);
        }
        if (isset($dimensions['height'])) {
            $variant->setHeight($dimensions['height']);
        }
        if (isset($dimensions['depth'])) {
            $variant->setDepth($dimensions['depth']);
        }
        if (isset($dimensions['weight'])) {
            $variant->setWeight($dimensions['weight']);
        }
    }

    /**
     * A "variant_prices" key is one product option value code, or several of them joined by a "+" for the
     * products having more than one option (the order of the codes does not matter).
     *
     * @param array<string, mixed> $options
     *
     * @return mixed the price matching the option values of the variant, the price of the product otherwise
     */
    private function priceOf(ProductVariantInterface $variant, array $options): mixed
    {
        /** @var array<array-key, mixed> $variantPrices */
        $variantPrices = $options['variant_prices'] ?? [];

        return $this->matchOptionValues($variantPrices, $variant) ?? $options['price'] ?? null;
    }

    /**
     * @param array<array-key, mixed> $valuesByOptionValues
     *
     * @return mixed the value whose key lists the option values of the variant, null when there is none
     */
    private function matchOptionValues(array $valuesByOptionValues, ProductVariantInterface $variant): mixed
    {
        $variantKey = $this->optionValuesKey($variant);
        foreach ($valuesByOptionValues as $key => $value) {
            if ($this->normalizeKey((string) $key) === $variantKey) {
                return $value;
            }
        }

        return null;
    }

    private function optionValuesKey(ProductVariantInterface $variant): string
    {
        $codes = [];
        foreach ($variant->getOptionValues() as $optionValue) {
            $codes[] = (string) $optionValue->getCode();
        }

        return $this->joinCodes($codes);
    }

    private function normalizeKey(string $key): string
    {
        return $this->joinCodes(explode('+', $key));
    }

    /** @param list<string> $codes */
    private function joinCodes(array $codes): string
    {
        $codes = array_map(trim(...), $codes);
        sort($codes);

        return implode('+', $codes);
    }

    private function toMinorUnit(mixed $amount): int
    {
        Assert::numeric($amount);

        return (int) round(((float) $amount) * 100.0);
    }
}
