<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusFixturesPlugin\Unit\Fixture\Factory;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\CoreBundle\Fixture\Factory\ExampleFactoryInterface;
use Sylius\Component\Core\Model\PaymentMethod;
use Webgriffe\SyliusFixturesPlugin\Fixture\Factory\PaymentMethodExampleFactory;

#[CoversClass(PaymentMethodExampleFactory::class)]
final class PaymentMethodExampleFactoryTest extends TestCase
{
    public function testItTranslatesTheNameTheDescriptionAndTheInstructions(): void
    {
        $paymentMethod = new PaymentMethod();
        $paymentMethod->setCode('bonifico_bancario');

        $decoratedFactory = $this->createMock(ExampleFactoryInterface::class);
        $decoratedFactory
            ->expects(self::once())
            ->method('create')
            ->with(['code' => 'bonifico_bancario'])
            ->willReturn($paymentMethod)
        ;

        (new PaymentMethodExampleFactory($decoratedFactory))->create([
            'code' => 'bonifico_bancario',
            'translations' => [
                'it_IT' => [
                    'name' => 'Bonifico bancario',
                    'description' => 'Si paga in anticipo.',
                    'instructions' => 'IBAN IT60 X054 2811 1010 0000 0123 456.',
                ],
                'en_US' => ['name' => 'Bank transfer', 'description' => 'Paid in advance.'],
            ],
        ]);

        $paymentMethod->setCurrentLocale('it_IT');
        $paymentMethod->setFallbackLocale('it_IT');
        self::assertSame('Bonifico bancario', $paymentMethod->getName());
        self::assertSame('Si paga in anticipo.', $paymentMethod->getDescription());
        self::assertSame('IBAN IT60 X054 2811 1010 0000 0123 456.', $paymentMethod->getInstructions());

        $paymentMethod->setCurrentLocale('en_US');
        $paymentMethod->setFallbackLocale('en_US');
        self::assertSame('Bank transfer', $paymentMethod->getName());
    }
}
