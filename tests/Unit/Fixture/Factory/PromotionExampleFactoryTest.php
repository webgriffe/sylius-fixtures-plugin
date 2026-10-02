<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusFixturesPlugin\Unit\Fixture\Factory;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\CoreBundle\Fixture\Factory\ExampleFactoryInterface;
use Sylius\Component\Core\Model\Promotion;
use Sylius\Component\Core\Model\PromotionCoupon;
use Webgriffe\SyliusFixturesPlugin\Fixture\Factory\PromotionExampleFactory;

#[CoversClass(PromotionExampleFactory::class)]
final class PromotionExampleFactoryTest extends TestCase
{
    public function testItTranslatesTheLabelOfThePromotion(): void
    {
        $promotion = new Promotion();
        $promotion->setCode('spedizione_gratuita');

        $decoratedFactory = $this->createMock(ExampleFactoryInterface::class);
        $decoratedFactory
            ->expects(self::once())
            ->method('create')
            ->with(['code' => 'spedizione_gratuita'])
            ->willReturn($promotion)
        ;

        (new PromotionExampleFactory($decoratedFactory))->create([
            'code' => 'spedizione_gratuita',
            'translations' => ['it_IT' => 'Spedizione gratuita sopra 100 €', 'fr_FR' => 'Livraison offerte dès 100 €'],
        ]);

        $promotion->setCurrentLocale('it_IT');
        $promotion->setFallbackLocale('it_IT');
        self::assertSame('Spedizione gratuita sopra 100 €', $promotion->getLabel());

        $promotion->setCurrentLocale('fr_FR');
        $promotion->setFallbackLocale('fr_FR');
        self::assertSame('Livraison offerte dès 100 €', $promotion->getLabel());
    }

    public function testItStopsTrackingTheUsageOfThePromotionAndOfItsCoupons(): void
    {
        $promotion = new Promotion();
        $staffCoupon = new PromotionCoupon();
        $staffCoupon->setCode('STAFF25');
        $promotion->addCoupon($staffCoupon);
        $eventCoupon = new PromotionCoupon();
        $eventCoupon->setCode('CANTINA');
        $promotion->addCoupon($eventCoupon);

        $decoratedFactory = $this->createMock(ExampleFactoryInterface::class);
        $decoratedFactory->method('create')->willReturn($promotion);

        (new PromotionExampleFactory($decoratedFactory))->create([
            'code' => 'staff',
            'track_usage' => false,
            'coupons' => [['code' => 'STAFF25', 'track_usage' => false], ['code' => 'CANTINA']],
        ]);

        self::assertFalse($promotion->isTrackUsage());
        self::assertFalse($staffCoupon->isTrackUsage());
        self::assertNull($staffCoupon->getTrackUsageSince());
        self::assertTrue($eventCoupon->isTrackUsage());
    }

    public function testItDoesNotForwardTheTrackUsageOfThePromotion(): void
    {
        $decoratedFactory = $this->createMock(ExampleFactoryInterface::class);
        $decoratedFactory
            ->expects(self::once())
            ->method('create')
            ->with(['code' => 'staff'])
            ->willReturn(new Promotion())
        ;

        (new PromotionExampleFactory($decoratedFactory))->create(['code' => 'staff', 'track_usage' => false]);
    }
}
