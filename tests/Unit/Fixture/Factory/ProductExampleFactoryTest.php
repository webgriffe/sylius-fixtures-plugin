<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusFixturesPlugin\Unit\Fixture\Factory;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\CoreBundle\Fixture\Factory\ExampleFactoryInterface;
use Sylius\Component\Core\Model\ChannelPricing;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariant;
use Sylius\Component\Product\Model\ProductOptionValue;
use Sylius\Component\Shipping\Model\ShippingCategory;
use Sylius\Component\Shipping\Model\ShippingCategoryInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Webgriffe\SyliusFixturesPlugin\Fixture\Factory\ProductExampleFactory;

#[CoversClass(ProductExampleFactory::class)]
final class ProductExampleFactoryTest extends TestCase
{
    public function testItSetsTheGivenPriceOnEveryChannelPricing(): void
    {
        $channelPricing = $this->createChannelPricing();
        $variant = $this->createVariant($channelPricing);

        $this->createFactory($this->createProduct($variant))->create(['price' => 12.5, 'original_price' => 15.0]);

        self::assertSame(1250, $channelPricing->getPrice());
        self::assertSame(1500, $channelPricing->getOriginalPrice());
    }

    public function testItPricesEveryVariantOnItsOwnProductOptionValue(): void
    {
        $twoGlassesPricing = $this->createChannelPricing();
        $twoGlasses = $this->createVariant($twoGlassesPricing);
        $twoGlasses->addOptionValue($this->createOptionValue('numero_calici_2'));

        $sixGlassesPricing = $this->createChannelPricing();
        $sixGlasses = $this->createVariant($sixGlassesPricing);
        $sixGlasses->addOptionValue($this->createOptionValue('numero_calici_6'));

        $product = $this->createProduct($twoGlasses);
        $product->addVariant($sixGlasses);

        $this->createFactory($product)->create([
            'variant_prices' => ['numero_calici_2' => 49.0, 'numero_calici_6' => 132.0],
        ]);

        self::assertSame(4900, $twoGlassesPricing->getPrice());
        self::assertSame(13200, $sixGlassesPricing->getPrice());
    }

    public function testItPricesTheVariantsOfAProductHavingSeveralOptions(): void
    {
        $twoDaysTwoPeoplePricing = $this->createChannelPricing();
        $twoDaysTwoPeople = $this->createVariant($twoDaysTwoPeoplePricing);
        $twoDaysTwoPeople->addOptionValue($this->createOptionValue('esperienza_2_giorni'));
        $twoDaysTwoPeople->addOptionValue($this->createOptionValue('partecipanti_2'));

        $oneDaySixPeoplePricing = $this->createChannelPricing();
        $oneDaySixPeople = $this->createVariant($oneDaySixPeoplePricing);
        $oneDaySixPeople->addOptionValue($this->createOptionValue('esperienza_1_giorno'));
        $oneDaySixPeople->addOptionValue($this->createOptionValue('partecipanti_6'));

        $product = $this->createProduct($twoDaysTwoPeople);
        $product->addVariant($oneDaySixPeople);

        $this->createFactory($product)->create([
            'variant_prices' => [
                'esperienza_2_giorni+partecipanti_2' => 420.0,
                // the order of the codes does not matter
                'partecipanti_6+esperienza_1_giorno' => 510.0,
            ],
        ]);

        self::assertSame(42000, $twoDaysTwoPeoplePricing->getPrice());
        self::assertSame(51000, $oneDaySixPeoplePricing->getPrice());
    }

    public function testItFallsBackToTheProductPriceForVariantsWithoutTheirOwnPrice(): void
    {
        $channelPricing = $this->createChannelPricing();
        $variant = $this->createVariant($channelPricing);
        $variant->addOptionValue($this->createOptionValue('numero_calici_8'));

        $this->createFactory($this->createProduct($variant))->create([
            'price' => 20.0,
            'variant_prices' => ['numero_calici_2' => 49.0],
        ]);

        self::assertSame(2000, $channelPricing->getPrice());
    }

    public function testItSetsTheShippingCategoryOnEveryVariant(): void
    {
        $variant = $this->createVariant($this->createChannelPricing());

        $this->createFactory($this->createProduct($variant))->create(['shipping_category' => 'fragile']);

        self::assertSame('fragile', $variant->getShippingCategory()?->getCode());
    }

    public function testItSetsTheGivenStockOnEveryVariant(): void
    {
        $variant = $this->createVariant($this->createChannelPricing());

        $this->createFactory($this->createProduct($variant))->create(['on_hand' => 42]);

        self::assertSame(42, $variant->getOnHand());
    }

    public function testItTranslatesTheProductInEveryGivenLocale(): void
    {
        $product = $this->createProduct($this->createVariant($this->createChannelPricing()));

        $this->createFactory($product)->create([
            'translations' => [
                'it_IT' => ['name' => 'Chianti DOCG', 'short_description' => 'Rosso toscano'],
                'en_US' => ['name' => 'Chianti DOCG', 'short_description' => 'Tuscan red'],
            ],
        ]);

        $product->setCurrentLocale('it_IT');
        $product->setFallbackLocale('it_IT');
        self::assertSame('Rosso toscano', $product->getShortDescription());

        $product->setCurrentLocale('en_US');
        $product->setFallbackLocale('en_US');
        self::assertSame('Tuscan red', $product->getShortDescription());
    }

    public function testItDoesNotForwardItsOwnOptionsToTheDecoratedFactory(): void
    {
        $product = $this->createProduct($this->createVariant($this->createChannelPricing()));

        $decoratedFactory = $this->createMock(ExampleFactoryInterface::class);
        $decoratedFactory
            ->expects(self::once())
            ->method('create')
            ->with(['code' => 'chianti'])
            ->willReturn($product)
        ;

        $factory = new ProductExampleFactory($decoratedFactory, $this->createShippingCategoryRepository());

        $factory->create([
            'code' => 'chianti',
            'price' => 9.5,
            'variant_prices' => ['numero_calici_2' => 49.0],
            'on_hand' => 10,
            'shipping_category' => 'fragile',
            'translations' => ['it_IT' => ['name' => 'Chianti DOCG']],
        ]);
    }

    private function createFactory(ProductInterface $product): ProductExampleFactory
    {
        $decoratedFactory = $this->createMock(ExampleFactoryInterface::class);
        $decoratedFactory->method('create')->willReturn($product);

        return new ProductExampleFactory($decoratedFactory, $this->createShippingCategoryRepository());
    }

    /** @return RepositoryInterface<ShippingCategoryInterface> */
    private function createShippingCategoryRepository(): RepositoryInterface
    {
        $shippingCategory = new ShippingCategory();
        $shippingCategory->setCode('fragile');

        $repository = $this->createMock(RepositoryInterface::class);
        $repository->method('findOneBy')->willReturn($shippingCategory);

        return $repository;
    }

    private function createChannelPricing(): ChannelPricing
    {
        $channelPricing = new ChannelPricing();
        $channelPricing->setChannelCode('ecommerce');
        $channelPricing->setPrice(100);

        return $channelPricing;
    }

    private function createVariant(ChannelPricing $channelPricing): ProductVariant
    {
        $variant = new ProductVariant();
        $variant->addChannelPricing($channelPricing);

        return $variant;
    }

    private function createOptionValue(string $code): ProductOptionValue
    {
        $optionValue = new ProductOptionValue();
        $optionValue->setCode($code);

        return $optionValue;
    }

    private function createProduct(ProductVariant $variant): ProductInterface
    {
        $product = new Product();
        $product->addVariant($variant);

        return $product;
    }
}
