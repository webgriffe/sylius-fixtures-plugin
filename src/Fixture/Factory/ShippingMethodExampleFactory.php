<?php

declare(strict_types=1);

namespace Webgriffe\SyliusFixturesPlugin\Fixture\Factory;

use Sylius\Bundle\CoreBundle\Fixture\Factory\ExampleFactoryInterface;
use Sylius\Component\Core\Model\ShippingMethodInterface;
use Webmozart\Assert\Assert;

/**
 * Adds the category requirement to the Sylius shipping method example factory, which always leaves it
 * to its default value ("match any").
 *
 * @implements ExampleFactoryInterface<ShippingMethodInterface>
 */
final readonly class ShippingMethodExampleFactory implements ExampleFactoryInterface
{
    private const CATEGORY_REQUIREMENTS = [
        'match_none' => ShippingMethodInterface::CATEGORY_REQUIREMENT_MATCH_NONE,
        'match_any' => ShippingMethodInterface::CATEGORY_REQUIREMENT_MATCH_ANY,
        'match_all' => ShippingMethodInterface::CATEGORY_REQUIREMENT_MATCH_ALL,
    ];

    /** @param ExampleFactoryInterface<ShippingMethodInterface> $decoratedFactory */
    public function __construct(
        private ExampleFactoryInterface $decoratedFactory,
    ) {
    }

    /** @param array<string, mixed> $options */
    #[\Override]
    public function create(array $options = []): ShippingMethodInterface
    {
        $categoryRequirement = $options['category_requirement'] ?? null;
        unset($options['category_requirement']);

        $shippingMethod = $this->decoratedFactory->create($options);

        if (null !== $categoryRequirement) {
            Assert::string($categoryRequirement);
            Assert::keyExists(self::CATEGORY_REQUIREMENTS, $categoryRequirement);

            $shippingMethod->setCategoryRequirement(self::CATEGORY_REQUIREMENTS[$categoryRequirement]);
        }

        return $shippingMethod;
    }
}
