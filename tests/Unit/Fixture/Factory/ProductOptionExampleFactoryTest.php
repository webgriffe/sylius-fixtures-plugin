<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusFixturesPlugin\Unit\Fixture\Factory;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\CoreBundle\Fixture\Factory\ExampleFactoryInterface;
use Sylius\Component\Product\Model\ProductOption;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionValue;
use Webgriffe\SyliusFixturesPlugin\Fixture\Factory\ProductOptionExampleFactory;

#[CoversClass(ProductOptionExampleFactory::class)]
final class ProductOptionExampleFactoryTest extends TestCase
{
    public function testItTranslatesTheNameOfTheOption(): void
    {
        $productOption = $this->createOption('numero_calici');

        $this->createFactory($productOption)->create([
            'code' => 'numero_calici',
            'translations' => ['it_IT' => 'Numero di calici', 'en_US' => 'Number of glasses'],
        ]);

        self::assertSame('Numero di calici', $this->nameIn($productOption, 'it_IT'));
        self::assertSame('Number of glasses', $this->nameIn($productOption, 'en_US'));
    }

    public function testItTranslatesTheValuesOfTheOption(): void
    {
        $value = new ProductOptionValue();
        $value->setCode('numero_calici_2');

        $productOption = $this->createOption('numero_calici');
        $productOption->addValue($value);

        $this->createFactory($productOption)->create([
            'code' => 'numero_calici',
            'values' => ['numero_calici_2' => ['it_IT' => '2 calici', 'fr_FR' => '2 verres']],
        ]);

        $value->setCurrentLocale('it_IT');
        $value->setFallbackLocale('it_IT');
        self::assertSame('2 calici', $value->getValue());

        $value->setCurrentLocale('fr_FR');
        $value->setFallbackLocale('fr_FR');
        self::assertSame('2 verres', $value->getValue());
    }

    public function testItGivesTheDecoratedFactoryOneValuePerCode(): void
    {
        $productOption = $this->createOption('numero_calici');

        $decoratedFactory = $this->createMock(ExampleFactoryInterface::class);
        $decoratedFactory
            ->expects(self::once())
            ->method('create')
            ->with(['code' => 'numero_calici', 'values' => ['numero_calici_2' => '2 calici']])
            ->willReturn($productOption)
        ;

        (new ProductOptionExampleFactory($decoratedFactory))->create([
            'code' => 'numero_calici',
            'translations' => ['it_IT' => 'Numero di calici'],
            'values' => ['numero_calici_2' => ['it_IT' => '2 calici', 'en_US' => '2 glasses']],
        ]);
    }

    private function createOption(string $code): ProductOption
    {
        $productOption = new ProductOption();
        $productOption->setCode($code);

        return $productOption;
    }

    private function nameIn(ProductOptionInterface $productOption, string $localeCode): ?string
    {
        $productOption->setCurrentLocale($localeCode);
        $productOption->setFallbackLocale($localeCode);

        return $productOption->getName();
    }

    private function createFactory(ProductOptionInterface $productOption): ProductOptionExampleFactory
    {
        $decoratedFactory = $this->createMock(ExampleFactoryInterface::class);
        $decoratedFactory->method('create')->willReturn($productOption);

        return new ProductOptionExampleFactory($decoratedFactory);
    }
}
