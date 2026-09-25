<?php

declare(strict_types=1);

namespace Webgriffe\SyliusFixturesPlugin\Fixture\Factory;

use Sylius\Bundle\CoreBundle\Fixture\Factory\ExampleFactoryInterface;
use Sylius\Component\Core\Model\PromotionInterface;

/**
 * Translates the label of a cart promotion, which Sylius writes identically in every locale. The description
 * is not translatable in Sylius, it stays on the promotion itself.
 *
 * @implements ExampleFactoryInterface<PromotionInterface>
 */
final readonly class PromotionExampleFactory implements ExampleFactoryInterface
{
    /** @param ExampleFactoryInterface<PromotionInterface> $decoratedFactory */
    public function __construct(
        private ExampleFactoryInterface $decoratedFactory,
    ) {
    }

    /** @param array<string, mixed> $options */
    #[\Override]
    public function create(array $options = []): PromotionInterface
    {
        /** @var array<string, string> $translations */
        $translations = $options['translations'] ?? [];
        unset($options['translations']);

        $promotion = $this->decoratedFactory->create($options);

        foreach ($translations as $localeCode => $label) {
            $promotion->setCurrentLocale($localeCode);
            $promotion->setFallbackLocale($localeCode);

            $promotion->setLabel($label);
        }

        return $promotion;
    }
}
