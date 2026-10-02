<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusFixturesPlugin\Unit\Fixture;

use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Currency\Model\Currency;
use Sylius\Component\Currency\Model\CurrencyInterface;
use Sylius\Component\Currency\Model\ExchangeRate;
use Sylius\Component\Currency\Model\ExchangeRateInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Sylius\Resource\Factory\FactoryInterface;
use Webgriffe\SyliusFixturesPlugin\Fixture\ExchangeRateFixture;

#[CoversClass(ExchangeRateFixture::class)]
final class ExchangeRateFixtureTest extends TestCase
{
    /** @var list<ExchangeRateInterface> */
    private array $persisted = [];

    public function testItCreatesTheExchangeRatesBetweenTheCurrencies(): void
    {
        $this->createFixture()->load(['exchange_rates' => [
            ['source_currency' => 'EUR', 'target_currency' => 'GBP', 'ratio' => 0.85373],
        ]]);

        self::assertCount(1, $this->persisted);
        self::assertSame('EUR', $this->persisted[0]->getSourceCurrency()?->getCode());
        self::assertSame('GBP', $this->persisted[0]->getTargetCurrency()?->getCode());
        self::assertSame(0.85373, $this->persisted[0]->getRatio());
    }

    public function testItRefusesAnUnknownCurrency(): void
    {
        $this->expectExceptionMessage('Unknown currency "XXX".');

        $this->createFixture()->load(['exchange_rates' => [
            ['source_currency' => 'EUR', 'target_currency' => 'XXX', 'ratio' => 1.0],
        ]]);
    }

    private function createFixture(): ExchangeRateFixture
    {
        /** @var RepositoryInterface<CurrencyInterface> $currencyRepository */
        $currencyRepository = $this->createMock(RepositoryInterface::class);
        $currencyRepository->method('findOneBy')->willReturnCallback(static function (array $criteria): ?Currency {
            if ('XXX' === $criteria['code']) {
                return null;
            }

            $currency = new Currency();
            $currency->setCode($criteria['code']);

            return $currency;
        });

        /** @var FactoryInterface<ExchangeRateInterface> $exchangeRateFactory */
        $exchangeRateFactory = $this->createMock(FactoryInterface::class);
        $exchangeRateFactory->method('createNew')->willReturnCallback(static fn (): ExchangeRate => new ExchangeRate());

        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager->method('persist')->willReturnCallback(function (object $object): void {
            self::assertInstanceOf(ExchangeRateInterface::class, $object);
            $this->persisted[] = $object;
        });

        return new ExchangeRateFixture($objectManager, $currencyRepository, $exchangeRateFactory);
    }
}
