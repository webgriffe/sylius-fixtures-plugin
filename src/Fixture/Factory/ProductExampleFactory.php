<?php

declare(strict_types=1);

namespace Webgriffe\SyliusFixturesPlugin\Fixture\Factory;

use Sylius\Bundle\CoreBundle\Fixture\Factory\ExampleFactoryInterface;
use Sylius\Component\Core\Model\ChannelPricingInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Shipping\Model\ShippingCategoryInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Webmozart\Assert\Assert;

/**
 * Adds fixed prices, per locale translations and stock to the Sylius product example factory,
 * which otherwise generates random prices and the same translation for every locale.
 *
 * Configurable products can price each variant through "variant_prices", keyed by product option value.
 * It also assigns the shipping category, which the Sylius factory never sets on the variants.
 *
 * @implements ExampleFactoryInterface<ProductInterface>
 */
final readonly class ProductExampleFactory implements ExampleFactoryInterface
{
    private const EXTRA_OPTIONS = ['price', 'variant_prices', 'original_price', 'on_hand', 'shipping_category', 'translations'];

    /**
     * @param ExampleFactoryInterface<ProductInterface> $decoratedFactory
     * @param RepositoryInterface<ShippingCategoryInterface> $shippingCategoryRepository
     */
    public function __construct(
        private ExampleFactoryInterface $decoratedFactory,
        private RepositoryInterface $shippingCategoryRepository,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    #[\Override]
    public function create(array $options = []): ProductInterface
    {
        $extraOptions = array_intersect_key($options, array_flip(self::EXTRA_OPTIONS));

        $product = $this->decoratedFactory->create(array_diff_key($options, $extraOptions));

        /** @var array<string, array<string, string>> $translations */
        $translations = $extraOptions['translations'] ?? [];
        $this->translate($product, $translations);

        foreach ($product->getVariants() as $variant) {
            Assert::isInstanceOf($variant, ProductVariantInterface::class);

            $this->applyStock($variant, $extraOptions);
            $this->applyPrices($variant, $extraOptions);
            $this->applyShippingCategory($variant, $extraOptions);
        }

        return $product;
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
            if (isset($translation['slug'])) {
                $product->setSlug($translation['slug']);
            }
            if (isset($translation['short_description'])) {
                $product->setShortDescription($translation['short_description']);
            }
            if (isset($translation['description'])) {
                $product->setDescription($translation['description']);
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

        /** @var ChannelPricingInterface $channelPricing */
        foreach ($variant->getChannelPricings() as $channelPricing) {
            if (null !== $price) {
                $channelPricing->setPrice($this->toMinorUnit($price));
            }
            if (isset($options['original_price'])) {
                $channelPricing->setOriginalPrice($this->toMinorUnit($options['original_price']));
            }
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
        /** @var array<string, mixed> $variantPrices */
        $variantPrices = $options['variant_prices'] ?? [];

        $variantKey = $this->optionValuesKey($variant);
        foreach ($variantPrices as $key => $price) {
            if ($this->normalizeKey($key) === $variantKey) {
                return $price;
            }
        }

        return $options['price'] ?? null;
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
