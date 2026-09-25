<?php

declare(strict_types=1);

namespace Webgriffe\SyliusFixturesPlugin\Fixture\Factory;

use Sylius\Bundle\CoreBundle\Fixture\Factory\ExampleFactoryInterface;
use Sylius\Component\Promotion\Model\CatalogPromotionInterface;

/**
 * Translates the label and the description of a catalog promotion, which Sylius writes identically in every
 * locale.
 *
 * @implements ExampleFactoryInterface<CatalogPromotionInterface>
 */
final readonly class CatalogPromotionExampleFactory implements ExampleFactoryInterface
{
    /** @param ExampleFactoryInterface<CatalogPromotionInterface> $decoratedFactory */
    public function __construct(
        private ExampleFactoryInterface $decoratedFactory,
    ) {
    }

    /** @param array<string, mixed> $options */
    #[\Override]
    public function create(array $options = []): CatalogPromotionInterface
    {
        /** @var array<string, array<string, string>> $translations */
        $translations = $options['translations'] ?? [];
        unset($options['translations']);

        $catalogPromotion = $this->decoratedFactory->create($options);

        foreach ($translations as $localeCode => $translation) {
            $catalogPromotion->setCurrentLocale($localeCode);
            // the fallback locale must match the current one, otherwise the fallback translation is overwritten
            $catalogPromotion->setFallbackLocale($localeCode);

            if (isset($translation['label'])) {
                $catalogPromotion->setLabel($translation['label']);
            }
            if (isset($translation['description'])) {
                $catalogPromotion->setDescription($translation['description']);
            }
        }

        return $catalogPromotion;
    }
}
