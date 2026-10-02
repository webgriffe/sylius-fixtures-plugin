<?php

declare(strict_types=1);

namespace Webgriffe\SyliusFixturesPlugin\Fixture\Factory;

use Sylius\Bundle\CoreBundle\Fixture\Factory\ExampleFactoryInterface;
use Sylius\Component\Core\Model\PromotionInterface;

/**
 * Translates the label of a cart promotion, which Sylius writes identically in every locale. The description
 * is not translatable in Sylius, it stays on the promotion itself.
 *
 * It also lets the promotion and its coupons stop tracking their usage, which the Sylius factory always leaves
 * on: their usage counter is then left untouched by the orders.
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

        $trackUsage = $options['track_usage'] ?? null;
        unset($options['track_usage']);

        $couponsTrackUsage = $this->couponsTrackUsage($options);

        $promotion = $this->decoratedFactory->create($options);

        $this->translate($promotion, $translations);

        if (is_bool($trackUsage)) {
            $promotion->setTrackUsage($trackUsage);
        }

        $this->trackCouponsUsage($promotion, $couponsTrackUsage);

        return $promotion;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, bool> whether each coupon, by code, tracks its usage
     */
    private function couponsTrackUsage(array $options): array
    {
        /** @var list<array<string, mixed>> $coupons */
        $coupons = $options['coupons'] ?? [];

        $couponsTrackUsage = [];
        foreach ($coupons as $coupon) {
            if (is_string($coupon['code'] ?? null) && is_bool($coupon['track_usage'] ?? null)) {
                $couponsTrackUsage[$coupon['code']] = $coupon['track_usage'];
            }
        }

        return $couponsTrackUsage;
    }

    /** @param array<string, string> $translations */
    private function translate(PromotionInterface $promotion, array $translations): void
    {
        foreach ($translations as $localeCode => $label) {
            $promotion->setCurrentLocale($localeCode);
            $promotion->setFallbackLocale($localeCode);

            $promotion->setLabel($label);
        }
    }

    /** @param array<string, bool> $couponsTrackUsage */
    private function trackCouponsUsage(PromotionInterface $promotion, array $couponsTrackUsage): void
    {
        foreach ($promotion->getCoupons() as $coupon) {
            $trackUsage = $couponsTrackUsage[(string) $coupon->getCode()] ?? null;
            if (null === $trackUsage) {
                continue;
            }

            $coupon->setTrackUsage($trackUsage);
        }
    }
}
