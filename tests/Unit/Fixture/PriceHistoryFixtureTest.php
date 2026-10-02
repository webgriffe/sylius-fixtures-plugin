<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusFixturesPlugin\Unit\Fixture;

use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\CoreBundle\PriceHistory\Processor\ProductLowestPriceBeforeDiscountProcessorInterface;
use Sylius\Component\Core\Factory\ChannelPricingLogEntryFactoryInterface;
use Sylius\Component\Core\Model\ChannelPricing;
use Sylius\Component\Core\Model\ChannelPricingLogEntry;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariant;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Webgriffe\SyliusFixturesPlugin\Fixture\PriceHistoryFixture;

#[CoversClass(PriceHistoryFixture::class)]
final class PriceHistoryFixtureTest extends TestCase
{
    public function testItLogsThePastPricesAndThenTheCurrentOne(): void
    {
        $channelPricing = new ChannelPricing();
        $channelPricing->setChannelCode('ecommerce');
        $channelPricing->setPrice(1200);
        $channelPricing->setOriginalPrice(1590);

        $loggedEntries = [];
        $fixture = $this->createFixture($channelPricing, $loggedEntries);

        $fixture->load(['products' => ['chianti' => ['entries' => [
            ['days_ago' => 50, 'price' => 15.90],
            ['days_ago' => 26, 'price' => 13.90],
        ]]]]);

        self::assertSame([1590, 1390, 1200], array_column($loggedEntries, 'price'));
        // the current price is logged last, as Sylius looks for the latest entry by id
        self::assertSame(1590, $loggedEntries[2]['original_price']);
    }

    public function testItLogsMutableDatesAsTheDoctrineMappingRequires(): void
    {
        $channelPricing = new ChannelPricing();
        $channelPricing->setChannelCode('ecommerce');
        $channelPricing->setPrice(1200);

        $loggedEntries = [];
        $this->createFixture($channelPricing, $loggedEntries)
            ->load(['products' => ['chianti' => ['entries' => [['days_ago' => 10, 'price' => 13.00]]]]])
        ;

        self::assertSame([true, true], array_column($loggedEntries, 'mutable_date'));
    }

    public function testItLetsSyliusComputeTheLowestPrice(): void
    {
        $channelPricing = new ChannelPricing();
        $channelPricing->setChannelCode('ecommerce');
        $channelPricing->setPrice(1200);

        $loggedEntries = [];
        $processor = $this->createMock(ProductLowestPriceBeforeDiscountProcessorInterface::class);
        $processor->expects(self::once())->method('process')->with($channelPricing);

        $this->createFixture($channelPricing, $loggedEntries, $processor)
            ->load(['products' => ['chianti' => ['entries' => [['days_ago' => 10, 'price' => 13.00]]]]])
        ;
    }

    /** @param array<int, array<string, int|bool|null>> $loggedEntries */
    private function createFixture(
        ChannelPricing $channelPricing,
        array &$loggedEntries,
        ?ProductLowestPriceBeforeDiscountProcessorInterface $processor = null,
    ): PriceHistoryFixture {
        $variant = new ProductVariant();
        $variant->addChannelPricing($channelPricing);

        $product = new Product();
        $product->setCode('chianti');
        $product->addVariant($variant);

        /** @var RepositoryInterface<ProductInterface> $productRepository */
        $productRepository = $this->createMock(RepositoryInterface::class);
        $productRepository->method('findOneBy')->willReturn($product);

        $logEntryRepository = $this->createMock(RepositoryInterface::class);
        $logEntryRepository->method('findBy')->willReturn([]);

        $factory = $this->createMock(ChannelPricingLogEntryFactoryInterface::class);
        $factory->method('create')->willReturnCallback(
            function (ChannelPricing $pricing, \DateTimeInterface $loggedAt, int $price, ?int $originalPrice = null) use (&$loggedEntries): ChannelPricingLogEntry {
                $loggedEntries[] = [
                    'price' => $price,
                    'original_price' => $originalPrice,
                    'mutable_date' => $loggedAt instanceof \DateTime,
                ];

                return new ChannelPricingLogEntry($pricing, $loggedAt, $price, $originalPrice);
            },
        );

        return new PriceHistoryFixture(
            $this->createMock(ObjectManager::class),
            $productRepository,
            $logEntryRepository,
            $factory,
            $processor ?? $this->createMock(ProductLowestPriceBeforeDiscountProcessorInterface::class),
        );
    }
}
