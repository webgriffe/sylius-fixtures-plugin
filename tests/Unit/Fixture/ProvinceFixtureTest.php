<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusFixturesPlugin\Unit\Fixture;

use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Addressing\Model\Country;
use Sylius\Component\Addressing\Model\CountryInterface;
use Sylius\Component\Addressing\Model\Province;
use Sylius\Component\Addressing\Model\ProvinceInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Sylius\Resource\Factory\FactoryInterface;
use Webgriffe\SyliusFixturesPlugin\Fixture\ProvinceFixture;

#[CoversClass(ProvinceFixture::class)]
final class ProvinceFixtureTest extends TestCase
{
    public function testItCreatesTheProvincesOfACountryWithTheirAbbreviation(): void
    {
        $country = new Country();
        $country->setCode('IT');

        $this->createFixture($country)->load(['countries' => ['IT' => [
            'IT-BO' => ['name' => 'Bologna', 'abbreviation' => 'BO'],
            'IT-MI' => ['name' => 'Milano', 'abbreviation' => 'MI'],
        ]]]);

        $provinces = [];
        foreach ($country->getProvinces() as $province) {
            $provinces[(string) $province->getCode()] = [$province->getName(), $province->getAbbreviation()];
        }

        self::assertSame(['IT-BO' => ['Bologna', 'BO'], 'IT-MI' => ['Milano', 'MI']], $provinces);
    }

    public function testItAcceptsAProvinceWithoutAbbreviation(): void
    {
        $country = new Country();
        $country->setCode('FR');

        $this->createFixture($country)->load(['countries' => ['FR' => [
            'FR-75' => ['name' => 'Paris'],
        ]]]);

        self::assertNull($country->getProvinces()->first()->getAbbreviation());
    }

    private function createFixture(CountryInterface $country): ProvinceFixture
    {
        /** @var RepositoryInterface<CountryInterface> $countryRepository */
        $countryRepository = $this->createMock(RepositoryInterface::class);
        $countryRepository->method('findOneBy')->willReturn($country);

        /** @var FactoryInterface<ProvinceInterface> $provinceFactory */
        $provinceFactory = $this->createMock(FactoryInterface::class);
        $provinceFactory->method('createNew')->willReturnCallback(static fn (): Province => new Province());

        return new ProvinceFixture($this->createMock(ObjectManager::class), $countryRepository, $provinceFactory);
    }
}
