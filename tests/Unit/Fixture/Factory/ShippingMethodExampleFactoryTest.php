<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusFixturesPlugin\Unit\Fixture\Factory;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\CoreBundle\Fixture\Factory\ExampleFactoryInterface;
use Sylius\Component\Core\Model\ShippingMethod;
use Sylius\Component\Core\Model\ShippingMethodInterface;
use Webgriffe\SyliusFixturesPlugin\Fixture\Factory\ShippingMethodExampleFactory;

#[CoversClass(ShippingMethodExampleFactory::class)]
final class ShippingMethodExampleFactoryTest extends TestCase
{
    public function testItSetsTheCategoryRequirement(): void
    {
        $shippingMethod = new ShippingMethod();

        $this->createFactory($shippingMethod)->create(['code' => 'courier', 'category_requirement' => 'match_all']);

        self::assertSame(
            ShippingMethodInterface::CATEGORY_REQUIREMENT_MATCH_ALL,
            $shippingMethod->getCategoryRequirement(),
        );
    }

    public function testItTranslatesTheNameAndTheDescription(): void
    {
        $shippingMethod = new ShippingMethod();

        $this->createFactory($shippingMethod)->create([
            'code' => 'courier',
            'translations' => [
                'it_IT' => ['name' => 'Corriere espresso', 'description' => 'Consegna in 24 ore.'],
                'en_US' => ['name' => 'Express courier', 'description' => 'Delivered in 24 hours.'],
            ],
        ]);

        $shippingMethod->setCurrentLocale('it_IT');
        $shippingMethod->setFallbackLocale('it_IT');
        self::assertSame('Corriere espresso', $shippingMethod->getName());
        self::assertSame('Consegna in 24 ore.', $shippingMethod->getDescription());

        $shippingMethod->setCurrentLocale('en_US');
        $shippingMethod->setFallbackLocale('en_US');
        self::assertSame('Express courier', $shippingMethod->getName());
    }

    public function testItSetsTheEstimatedDeliveryTime(): void
    {
        $shippingMethod = new ShippingMethod();

        $this->createFactory($shippingMethod)->create([
            'code' => 'courier',
            'min_delivery_time_days' => 1,
            'max_delivery_time_days' => 2,
        ]);

        self::assertSame(1, $shippingMethod->getMinDeliveryTimeDays());
        self::assertSame(2, $shippingMethod->getMaxDeliveryTimeDays());
    }

    public function testItDoesNotForwardItsOwnOptionsToTheDecoratedFactory(): void
    {
        $decoratedFactory = $this->createMock(ExampleFactoryInterface::class);
        $decoratedFactory
            ->expects(self::once())
            ->method('create')
            ->with(['code' => 'courier'])
            ->willReturn(new ShippingMethod())
        ;

        (new ShippingMethodExampleFactory($decoratedFactory))->create([
            'code' => 'courier',
            'category_requirement' => 'match_any',
            'min_delivery_time_days' => 1,
            'max_delivery_time_days' => 2,
            'translations' => ['it_IT' => ['name' => 'Corriere espresso']],
        ]);
    }

    private function createFactory(ShippingMethodInterface $shippingMethod): ShippingMethodExampleFactory
    {
        $decoratedFactory = $this->createMock(ExampleFactoryInterface::class);
        $decoratedFactory->method('create')->willReturn($shippingMethod);

        return new ShippingMethodExampleFactory($decoratedFactory);
    }
}
