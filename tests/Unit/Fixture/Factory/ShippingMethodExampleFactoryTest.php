<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusFixturesPlugin\Unit\Fixture\Factory;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\CoreBundle\Fixture\Factory\ShippingMethodExampleFactory as BaseShippingMethodExampleFactory;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ShippingMethod;
use Sylius\Component\Core\Model\ShippingMethodInterface;
use Sylius\Component\Locale\Model\Locale;
use Sylius\Component\Shipping\Model\ShippingMethodRule;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Sylius\Resource\Factory\Factory;
use Sylius\Resource\Factory\FactoryInterface;
use Webgriffe\SyliusFixturesPlugin\Fixture\Factory\ShippingMethodExampleFactory;

#[CoversClass(ShippingMethodExampleFactory::class)]
final class ShippingMethodExampleFactoryTest extends TestCase
{
    public function testItIsTheSyliusFactoryTheBehatContextsRequire(): void
    {
        self::assertInstanceOf(BaseShippingMethodExampleFactory::class, $this->createFactory(new ShippingMethod()));
    }

    public function testItSetsTheCategoryRequirement(): void
    {
        $shippingMethod = new ShippingMethod();

        $this->createFactory($shippingMethod)->create($this->options(['category_requirement' => 'match_all']));

        self::assertSame(
            ShippingMethodInterface::CATEGORY_REQUIREMENT_MATCH_ALL,
            $shippingMethod->getCategoryRequirement(),
        );
    }

    public function testItTranslatesTheNameAndTheDescription(): void
    {
        $shippingMethod = new ShippingMethod();

        $this->createFactory($shippingMethod)->create($this->options([
            'translations' => [
                'it_IT' => ['name' => 'Corriere espresso', 'description' => 'Consegna in 24 ore.'],
                'en_US' => ['name' => 'Express courier', 'description' => 'Delivered in 24 hours.'],
            ],
        ]));

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

        $this->createFactory($shippingMethod)->create($this->options([
            'min_delivery_time_days' => 1,
            'max_delivery_time_days' => 2,
        ]));

        self::assertSame(1, $shippingMethod->getMinDeliveryTimeDays());
        self::assertSame(2, $shippingMethod->getMaxDeliveryTimeDays());
    }

    public function testItAddsTheRules(): void
    {
        $shippingMethod = new ShippingMethod();

        $this->createFactory($shippingMethod)->create($this->options([
            'rules' => [
                ['type' => 'total_weight_less_than_or_equal', 'configuration' => ['weight' => 20]],
                ['type' => 'order_total_greater_than_or_equal', 'configuration' => ['web' => ['amount' => 5000]]],
            ],
        ]));

        $rules = $shippingMethod->getRules()->toArray();
        self::assertCount(2, $rules);
        self::assertSame('total_weight_less_than_or_equal', $rules[0]->getType());
        self::assertSame(['weight' => 20], $rules[0]->getConfiguration());
        self::assertSame($shippingMethod, $rules[0]->getShippingMethod());
        self::assertSame(['web' => ['amount' => 5000]], $rules[1]->getConfiguration());
    }

    public function testItLeavesTheSyliusOptionsToTheSyliusFactory(): void
    {
        $shippingMethod = new ShippingMethod();

        $this->createFactory($shippingMethod)->create($this->options([
            'category_requirement' => 'match_any',
            'rules' => [['type' => 'total_weight_less_than_or_equal', 'configuration' => ['weight' => 20]]],
        ]));

        self::assertSame('corriere', $shippingMethod->getCode());
        self::assertSame('flat_rate', $shippingMethod->getCalculator());
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function options(array $options): array
    {
        return $options + [
            'code' => 'corriere',
            'name' => 'Corriere',
            'description' => 'Consegna a domicilio.',
            'enabled' => true,
            'zone' => null,
            'channels' => [],
            'calculator' => ['type' => 'flat_rate', 'configuration' => []],
        ];
    }

    private function createFactory(ShippingMethodInterface $shippingMethod): ShippingMethodExampleFactory
    {
        /** @var FactoryInterface<ShippingMethodInterface> $shippingMethodFactory */
        $shippingMethodFactory = $this->createMock(FactoryInterface::class);
        $shippingMethodFactory->method('createNew')->willReturn($shippingMethod);

        $locale = new Locale();
        $locale->setCode('it_IT');
        $localeRepository = $this->createMock(RepositoryInterface::class);
        $localeRepository->method('findAll')->willReturn([$locale]);

        $factory = new ShippingMethodExampleFactory(
            $shippingMethodFactory,
            $this->createMock(RepositoryInterface::class),
            $this->createMock(RepositoryInterface::class),
            $localeRepository,
            $this->createMock(ChannelRepositoryInterface::class),
            $this->createMock(RepositoryInterface::class),
        );
        $factory->setRuleFactory(new Factory(ShippingMethodRule::class));

        return $factory;
    }
}
