<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusFixturesPlugin\Unit\Fixture\Factory;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\CoreBundle\Fixture\Factory\ExampleFactoryInterface;
use Sylius\Component\Product\Model\ProductAssociationType;
use Webgriffe\SyliusFixturesPlugin\Fixture\Factory\ProductAssociationTypeExampleFactory;

#[CoversClass(ProductAssociationTypeExampleFactory::class)]
final class ProductAssociationTypeExampleFactoryTest extends TestCase
{
    public function testItTranslatesTheNameOfTheAssociationType(): void
    {
        $associationType = new ProductAssociationType();
        $associationType->setCode('si_abbina_con');

        $decoratedFactory = $this->createMock(ExampleFactoryInterface::class);
        $decoratedFactory
            ->expects(self::once())
            ->method('create')
            ->with(['code' => 'si_abbina_con', 'name' => 'Si abbina con'])
            ->willReturn($associationType)
        ;

        (new ProductAssociationTypeExampleFactory($decoratedFactory))->create([
            'code' => 'si_abbina_con',
            'name' => 'Si abbina con',
            'translations' => ['it_IT' => 'Si abbina con', 'fr_FR' => "S'accorde avec"],
        ]);

        $associationType->setCurrentLocale('it_IT');
        $associationType->setFallbackLocale('it_IT');
        self::assertSame('Si abbina con', $associationType->getName());

        $associationType->setCurrentLocale('fr_FR');
        $associationType->setFallbackLocale('fr_FR');
        self::assertSame("S'accorde avec", $associationType->getName());
    }
}
