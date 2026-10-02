<?php

declare(strict_types=1);

namespace Webgriffe\SyliusFixturesPlugin\Fixture\Factory;

use Sylius\Bundle\CoreBundle\Fixture\Factory\ShippingMethodExampleFactory as BaseShippingMethodExampleFactory;
use Sylius\Component\Core\Model\ShippingMethodInterface;
use Sylius\Component\Shipping\Model\ShippingMethodRuleInterface;
use Sylius\Resource\Factory\FactoryInterface;
use Webmozart\Assert\Assert;

/**
 * Adds the category requirement to the Sylius shipping method example factory, which always leaves it
 * to its default value ("match any"), and the rules, which it does not support at all.
 *
 * It extends the Sylius factory instead of decorating it: the Behat contexts of Sylius require its class.
 */
final class ShippingMethodExampleFactory extends BaseShippingMethodExampleFactory
{
    private const CATEGORY_REQUIREMENTS = [
        'match_none' => ShippingMethodInterface::CATEGORY_REQUIREMENT_MATCH_NONE,
        'match_any' => ShippingMethodInterface::CATEGORY_REQUIREMENT_MATCH_ANY,
        'match_all' => ShippingMethodInterface::CATEGORY_REQUIREMENT_MATCH_ALL,
    ];

    /** @var FactoryInterface<ShippingMethodRuleInterface>|null */
    private ?FactoryInterface $ruleFactory = null;

    /**
     * Injected by a setter, so that the arguments of the Sylius factory can be kept as they are.
     *
     * @param FactoryInterface<ShippingMethodRuleInterface> $ruleFactory
     */
    public function setRuleFactory(FactoryInterface $ruleFactory): void
    {
        $this->ruleFactory = $ruleFactory;
    }

    /** @param array<string, mixed> $options */
    #[\Override]
    public function create(array $options = []): ShippingMethodInterface
    {
        $categoryRequirement = $options['category_requirement'] ?? null;
        unset($options['category_requirement']);

        /** @var array<string, array<string, string>> $translations */
        $translations = $options['translations'] ?? [];
        unset($options['translations']);

        $minDeliveryTimeDays = $options['min_delivery_time_days'] ?? null;
        $maxDeliveryTimeDays = $options['max_delivery_time_days'] ?? null;
        unset($options['min_delivery_time_days'], $options['max_delivery_time_days']);

        /** @var list<array{type: string, configuration: array<string, mixed>}> $rules */
        $rules = $options['rules'] ?? [];
        unset($options['rules']);

        $shippingMethod = parent::create($options);

        $this->translate($shippingMethod, $translations);
        $this->addRules($shippingMethod, $rules);

        if (null !== $minDeliveryTimeDays) {
            Assert::integer($minDeliveryTimeDays);
            $shippingMethod->setMinDeliveryTimeDays($minDeliveryTimeDays);
        }
        if (null !== $maxDeliveryTimeDays) {
            Assert::integer($maxDeliveryTimeDays);
            $shippingMethod->setMaxDeliveryTimeDays($maxDeliveryTimeDays);
        }

        if (null !== $categoryRequirement) {
            Assert::string($categoryRequirement);
            Assert::keyExists(self::CATEGORY_REQUIREMENTS, $categoryRequirement);

            $shippingMethod->setCategoryRequirement(self::CATEGORY_REQUIREMENTS[$categoryRequirement]);
        }

        return $shippingMethod;
    }

    /** @param list<array{type: string, configuration: array<string, mixed>}> $rules */
    private function addRules(ShippingMethodInterface $shippingMethod, array $rules): void
    {
        if ([] === $rules) {
            return;
        }

        Assert::notNull($this->ruleFactory, 'The shipping method rule factory has not been injected.');

        foreach ($rules as $ruleOptions) {
            $rule = $this->ruleFactory->createNew();
            $rule->setType($ruleOptions['type']);
            $rule->setConfiguration($ruleOptions['configuration']);

            $shippingMethod->addRule($rule);
        }
    }

    /** @param array<string, array<string, string>> $translations */
    private function translate(ShippingMethodInterface $shippingMethod, array $translations): void
    {
        foreach ($translations as $localeCode => $translation) {
            $shippingMethod->setCurrentLocale($localeCode);
            // the fallback locale must match the current one, otherwise the fallback translation is overwritten
            $shippingMethod->setFallbackLocale($localeCode);

            if (isset($translation['name'])) {
                $shippingMethod->setName($translation['name']);
            }
            if (isset($translation['description'])) {
                $shippingMethod->setDescription($translation['description']);
            }
        }
    }
}
