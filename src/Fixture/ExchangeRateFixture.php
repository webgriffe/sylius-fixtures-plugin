<?php

declare(strict_types=1);

namespace Webgriffe\SyliusFixturesPlugin\Fixture;

use Doctrine\Persistence\ObjectManager;
use Sylius\Bundle\FixturesBundle\Fixture\FixtureInterface;
use Sylius\Component\Currency\Model\CurrencyInterface;
use Sylius\Component\Currency\Model\ExchangeRateInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Sylius\Resource\Factory\FactoryInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Webmozart\Assert\Assert;

/**
 * Creates the exchange rates between the currencies of the shop, which Sylius has no fixture for: without them
 * a price shown in another currency is the same number as in the base currency.
 *
 * It runs after the currencies exist, so declare it after the "currency" fixture.
 */
final class ExchangeRateFixture implements FixtureInterface
{
    /**
     * @param RepositoryInterface<CurrencyInterface> $currencyRepository
     * @param FactoryInterface<ExchangeRateInterface> $exchangeRateFactory
     */
    public function __construct(
        private readonly ObjectManager $objectManager,
        private readonly RepositoryInterface $currencyRepository,
        private readonly FactoryInterface $exchangeRateFactory,
    ) {
    }

    public function getName(): string
    {
        return 'exchange_rate';
    }

    /** @param array<mixed> $options */
    public function load(array $options): void
    {
        /** @var list<array{source_currency: string, target_currency: string, ratio: float}> $exchangeRates */
        $exchangeRates = $options['exchange_rates'] ?? [];

        foreach ($exchangeRates as $exchangeRate) {
            $this->createExchangeRate($exchangeRate);
        }

        $this->objectManager->flush();
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder($this->getName());

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('exchange_rates')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('source_currency')->isRequired()->cannotBeEmpty()->end()
                            ->scalarNode('target_currency')->isRequired()->cannotBeEmpty()->end()
                            ->floatNode('ratio')->isRequired()->min(0)->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }

    /** @param array{source_currency: string, target_currency: string, ratio: float} $options */
    private function createExchangeRate(array $options): void
    {
        $exchangeRate = $this->exchangeRateFactory->createNew();
        $exchangeRate->setSourceCurrency($this->currency($options['source_currency']));
        $exchangeRate->setTargetCurrency($this->currency($options['target_currency']));
        $exchangeRate->setRatio($options['ratio']);

        $this->objectManager->persist($exchangeRate);
    }

    private function currency(string $code): CurrencyInterface
    {
        $currency = $this->currencyRepository->findOneBy(['code' => $code]);
        Assert::isInstanceOf($currency, CurrencyInterface::class, sprintf('Unknown currency "%s".', $code));

        return $currency;
    }
}
