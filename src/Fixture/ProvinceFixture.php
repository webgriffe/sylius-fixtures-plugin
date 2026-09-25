<?php

declare(strict_types=1);

namespace Webgriffe\SyliusFixturesPlugin\Fixture;

use Doctrine\Persistence\ObjectManager;
use Sylius\Bundle\FixturesBundle\Fixture\FixtureInterface;
use Sylius\Component\Addressing\Model\CountryInterface;
use Sylius\Component\Addressing\Model\ProvinceInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Sylius\Resource\Factory\FactoryInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Webmozart\Assert\Assert;

/**
 * Creates the provinces of a country with their abbreviation, which the geographical fixture of Sylius leaves
 * empty: it only takes a code and a name.
 *
 * It runs after the countries exist, so declare it after the "geographical" fixture.
 */
final class ProvinceFixture implements FixtureInterface
{
    /**
     * @param RepositoryInterface<CountryInterface> $countryRepository
     * @param FactoryInterface<ProvinceInterface> $provinceFactory
     */
    public function __construct(
        private readonly ObjectManager $objectManager,
        private readonly RepositoryInterface $countryRepository,
        private readonly FactoryInterface $provinceFactory,
    ) {
    }

    public function getName(): string
    {
        return 'province';
    }

    /** @param array<mixed> $options */
    public function load(array $options): void
    {
        /** @var array<string, array<string, array<string, string>>> $countries */
        $countries = $options['countries'] ?? [];

        foreach ($countries as $countryCode => $provinces) {
            $country = $this->countryRepository->findOneBy(['code' => $countryCode]);
            Assert::isInstanceOf($country, CountryInterface::class, sprintf('Unknown country "%s".', $countryCode));

            foreach ($provinces as $provinceCode => $province) {
                $this->createProvince($country, $provinceCode, $province);
            }
        }

        $this->objectManager->flush();
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder($this->getName());

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('countries')
                    ->useAttributeAsKey('country_code')
                    ->arrayPrototype()
                        // the province codes carry a dash, which Symfony would turn into an underscore
                        ->normalizeKeys(false)
                        ->useAttributeAsKey('province_code')
                        ->arrayPrototype()
                            ->children()
                                ->scalarNode('name')->isRequired()->cannotBeEmpty()->end()
                                ->scalarNode('abbreviation')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }

    /** @param array<string, string> $options */
    private function createProvince(CountryInterface $country, string $code, array $options): void
    {
        $province = $this->provinceFactory->createNew();
        $province->setCode($code);
        $province->setName($options['name']);
        $province->setAbbreviation($options['abbreviation'] ?? null);

        $country->addProvince($province);

        $this->objectManager->persist($province);
    }
}
