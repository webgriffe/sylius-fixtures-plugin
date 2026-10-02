<?php

declare(strict_types=1);

namespace Webgriffe\SyliusFixturesPlugin\Fixture;

use Doctrine\Persistence\ObjectManager;
use Sylius\Bundle\CoreBundle\PriceHistory\Processor\ProductLowestPriceBeforeDiscountProcessorInterface;
use Sylius\Bundle\FixturesBundle\Fixture\FixtureInterface;
use Sylius\Component\Core\Factory\ChannelPricingLogEntryFactoryInterface;
use Sylius\Component\Core\Model\ChannelPricingInterface;
use Sylius\Component\Core\Model\ChannelPricingLogEntryInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Webmozart\Assert\Assert;

/**
 * Writes the price history of a product, which the shop uses to show the lowest price of the last days as
 * required by the Omnibus directive. Sylius only logs the price at the moment the fixtures run, so without
 * this fixture every product looks like it has always had its current price.
 */
final class PriceHistoryFixture implements FixtureInterface
{
    /**
     * @param RepositoryInterface<ProductInterface> $productRepository
     * @param RepositoryInterface<ChannelPricingLogEntryInterface> $channelPricingLogEntryRepository
     * @param ChannelPricingLogEntryFactoryInterface<ChannelPricingLogEntryInterface> $channelPricingLogEntryFactory
     */
    public function __construct(
        private readonly ObjectManager $objectManager,
        private readonly RepositoryInterface $productRepository,
        private readonly RepositoryInterface $channelPricingLogEntryRepository,
        private readonly ChannelPricingLogEntryFactoryInterface $channelPricingLogEntryFactory,
        private readonly ProductLowestPriceBeforeDiscountProcessorInterface $lowestPriceProcessor,
    ) {
    }

    public function getName(): string
    {
        return 'price_history';
    }

    /** @param array<mixed> $options */
    public function load(array $options): void
    {
        /** @var array<string, array<string, mixed>> $products */
        $products = $options['products'] ?? [];

        $channelPricings = [];
        foreach ($products as $productCode => $productOptions) {
            $product = $this->productRepository->findOneBy(['code' => $productCode]);
            Assert::isInstanceOf($product, ProductInterface::class, sprintf('Unknown product "%s".', $productCode));

            /** @var list<array<string, mixed>> $entries */
            $entries = $productOptions['entries'] ?? [];
            foreach ($product->getVariants() as $variant) {
                Assert::isInstanceOf($variant, ProductVariantInterface::class);

                foreach ($variant->getChannelPricings() as $channelPricing) {
                    $this->logEntries($channelPricing, $entries);

                    $channelPricings[] = $channelPricing;
                }
            }
        }

        // the history has to be readable by the processor, which queries it from the database
        $this->objectManager->flush();

        foreach ($channelPricings as $channelPricing) {
            $this->lowestPriceProcessor->process($channelPricing);
        }

        $this->objectManager->flush();
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder($this->getName());

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('products')
                    ->useAttributeAsKey('code')
                    ->arrayPrototype()
                        ->children()
                            ->arrayNode('entries')
                                ->arrayPrototype()
                                    ->children()
                                        ->integerNode('days_ago')->isRequired()->min(0)->end()
                                        ->floatNode('price')->isRequired()->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }

    /**
     * Sylius looks for the latest log entry by id, so the history has to be written in chronological order:
     * the entry it wrote when the product was created is replaced by an identical one, written last.
     *
     * The dates are mutable: the column is mapped as "datetime", which DBAL 4 refuses to fill with an immutable date.
     *
     * @param list<array<string, mixed>> $entries
     */
    private function logEntries(ChannelPricingInterface $channelPricing, array $entries): void
    {
        foreach ($this->channelPricingLogEntryRepository->findBy(['channelPricing' => $channelPricing]) as $existingEntry) {
            $this->objectManager->remove($existingEntry);
        }
        $this->objectManager->flush();

        foreach ($entries as $entry) {
            $daysAgo = $entry['days_ago'];
            $price = $entry['price'];
            Assert::integer($daysAgo);
            Assert::numeric($price);

            $logEntry = $this->channelPricingLogEntryFactory->create(
                $channelPricing,
                new \DateTime(sprintf('-%d days', $daysAgo)),
                (int) round(((float) $price) * 100.0),
            );

            $this->objectManager->persist($logEntry);
        }

        $this->objectManager->persist($this->channelPricingLogEntryFactory->create(
            $channelPricing,
            new \DateTime(),
            $channelPricing->getPrice() ?? 0,
            $channelPricing->getOriginalPrice(),
        ));
    }
}
