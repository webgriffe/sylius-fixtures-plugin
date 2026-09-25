<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusFixturesPlugin\Unit\Fixture\Factory;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\CoreBundle\Fixture\Factory\ExampleFactoryInterface;
use Sylius\Component\Promotion\Model\CatalogPromotion;
use Webgriffe\SyliusFixturesPlugin\Fixture\Factory\CatalogPromotionExampleFactory;

#[CoversClass(CatalogPromotionExampleFactory::class)]
final class CatalogPromotionExampleFactoryTest extends TestCase
{
    public function testItTranslatesTheLabelAndTheDescription(): void
    {
        $catalogPromotion = new CatalogPromotion();
        $catalogPromotion->setCode('promo_bianchi');

        $decoratedFactory = $this->createMock(ExampleFactoryInterface::class);
        $decoratedFactory
            ->expects(self::once())
            ->method('create')
            ->with(['code' => 'promo_bianchi'])
            ->willReturn($catalogPromotion)
        ;

        (new CatalogPromotionExampleFactory($decoratedFactory))->create([
            'code' => 'promo_bianchi',
            'translations' => [
                'it_IT' => ['label' => 'Settimana dei bianchi', 'description' => 'Bianchi scontati del 10%.'],
                'en_US' => ['label' => 'White wine week', 'description' => 'Whites are 10% off.'],
            ],
        ]);

        $catalogPromotion->setCurrentLocale('it_IT');
        $catalogPromotion->setFallbackLocale('it_IT');
        self::assertSame('Settimana dei bianchi', $catalogPromotion->getLabel());
        self::assertSame('Bianchi scontati del 10%.', $catalogPromotion->getDescription());

        $catalogPromotion->setCurrentLocale('en_US');
        $catalogPromotion->setFallbackLocale('en_US');
        self::assertSame('White wine week', $catalogPromotion->getLabel());
    }
}
