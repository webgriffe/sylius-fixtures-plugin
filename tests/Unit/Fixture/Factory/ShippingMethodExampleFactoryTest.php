<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusFixturesPlugin\Unit\Fixture\Factory;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\CoreBundle\Fixture\Factory\ExampleFactoryInterface;
use Sylius\Component\Core\Model\ShippingMethod;
use Sylius\Component\Core\Model\ShippingMethodInterface;
use Sylius\Component\Shipping\Model\ShippingMethodRule;
use Sylius\Resource\Factory\Factory;
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

    public function testItAddsTheRules(): void
    {
        $shippingMethod = new ShippingMethod();

        $this->createFactory($shippingMethod)->create([
            'code' => 'courier',
            'rules' => [
                ['type' => 'total_weight_less_than_or_equal', 'configuration' => ['weight' => 20]],
                ['type' => 'order_total_greater_than_or_equal', 'configuration' => ['web' => ['amount' => 5000]]],
            ],
        ]);

        $rules = $shippingMethod->getRules()->toArray();
        self::assertCount(2, $rules);
        self::assertSame('total_weight_less_than_or_equal', $rules[0]->getType());
        self::assertSame(['weight' => 20], $rules[0]->getConfiguration());
        self::assertSame($shippingMethod, $rules[0]->getShippingMethod());
        self::assertSame(['web' => ['amount' => 5000]], $rules[1]->getConfiguration());
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

        (new ShippingMethodExampleFactory($decoratedFactory, new Factory(ShippingMethodRule::class)))->create([
            'code' => 'courier',
            'category_requirement' => 'match_any',
            'min_delivery_time_days' => 1,
            'max_delivery_time_days' => 2,
            'rules' => [['type' => 'total_weight_less_than_or_equal', 'configuration' => ['weight' => 20]]],
            'translations' => ['it_IT' => ['name' => 'Corriere espresso']],
        ]);
    }

    private function createFactory(ShippingMethodInterface $shippingMethod): ShippingMethodExampleFactory
    {
        $decoratedFactory = $this->createMock(ExampleFactoryInterface::class);
        $decoratedFactory->method('create')->willReturn($shippingMethod);

        return new ShippingMethodExampleFactory($decoratedFactory, new Factory(ShippingMethodRule::class));
    }
}
