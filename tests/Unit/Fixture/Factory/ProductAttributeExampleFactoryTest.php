<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusFixturesPlugin\Unit\Fixture\Factory;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\CoreBundle\Fixture\Factory\ExampleFactoryInterface;
use Sylius\Component\Product\Model\ProductAttribute;
use Webgriffe\SyliusFixturesPlugin\Fixture\Factory\ProductAttributeExampleFactory;

#[CoversClass(ProductAttributeExampleFactory::class)]
final class ProductAttributeExampleFactoryTest extends TestCase
{
    public function testItTranslatesTheNameOfTheAttribute(): void
    {
        $attribute = new ProductAttribute();
        $attribute->setCode('vino_cantina');

        $decoratedFactory = $this->createMock(ExampleFactoryInterface::class);
        $decoratedFactory
            ->expects(self::once())
            ->method('create')
            ->with(['code' => 'vino_cantina', 'type' => 'text'])
            ->willReturn($attribute)
        ;

        (new ProductAttributeExampleFactory($decoratedFactory))->create([
            'code' => 'vino_cantina',
            'type' => 'text',
            'translations' => ['it_IT' => 'Cantina', 'en_US' => 'Winery'],
        ]);

        $attribute->setCurrentLocale('it_IT');
        $attribute->setFallbackLocale('it_IT');
        self::assertSame('Cantina', $attribute->getName());

        $attribute->setCurrentLocale('en_US');
        $attribute->setFallbackLocale('en_US');
        self::assertSame('Winery', $attribute->getName());
    }
}
