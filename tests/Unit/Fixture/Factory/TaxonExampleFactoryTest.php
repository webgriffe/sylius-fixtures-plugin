<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusFixturesPlugin\Unit\Fixture\Factory;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\CoreBundle\Fixture\Factory\ExampleFactoryInterface;
use Sylius\Component\Core\Model\ImageInterface;
use Sylius\Component\Core\Model\Taxon;
use Sylius\Component\Core\Model\TaxonImage;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Core\Uploader\ImageUploaderInterface;
use Sylius\Resource\Factory\FactoryInterface;
use Symfony\Component\Config\FileLocatorInterface;
use Webgriffe\SyliusFixturesPlugin\Fixture\Factory\TaxonExampleFactory;

#[CoversClass(TaxonExampleFactory::class)]
final class TaxonExampleFactoryTest extends TestCase
{
    private const IMAGE_PATH = __DIR__ . '/../../../../fixtures/images/wines/enoteca_1.webp';

    public function testItAddsTheImagesToTheTaxonAndToItsChildren(): void
    {
        $child = new Taxon();
        $child->setCode('vini_rossi');

        $root = new Taxon();
        $root->setCode('vini');
        $root->addChild($child);

        $factory = $this->createFactory($root);

        $factory->create([
            'code' => 'vini',
            'images' => [['path' => self::IMAGE_PATH, 'type' => 'main']],
            'children' => [
                ['code' => 'vini_rossi', 'images' => [['path' => self::IMAGE_PATH, 'type' => 'main']]],
            ],
        ]);

        self::assertCount(1, $root->getImages());
        self::assertCount(1, $child->getImages());
        self::assertSame('main', $root->getImages()->first()->getType());
    }

    public function testItLeavesTheTaxonsWithoutImagesAlone(): void
    {
        $taxon = new Taxon();
        $taxon->setCode('calici');

        $this->createFactory($taxon)->create(['code' => 'calici']);

        self::assertCount(0, $taxon->getImages());
    }

    public function testItDoesNotForwardTheImagesToTheDecoratedFactory(): void
    {
        $taxon = new Taxon();
        $taxon->setCode('vini');

        $decoratedFactory = $this->createMock(ExampleFactoryInterface::class);
        $decoratedFactory
            ->expects(self::once())
            ->method('create')
            ->with(['code' => 'vini', 'children' => [['code' => 'vini_rossi']]])
            ->willReturn($taxon)
        ;

        $factory = new TaxonExampleFactory(
            $decoratedFactory,
            $this->createImageFactory(),
            $this->createMock(ImageUploaderInterface::class),
            $this->createFileLocator(),
        );

        $factory->create([
            'code' => 'vini',
            'images' => [['path' => self::IMAGE_PATH]],
            'children' => [['code' => 'vini_rossi', 'images' => [['path' => self::IMAGE_PATH]]]],
        ]);
    }

    private function createFactory(TaxonInterface $taxon): TaxonExampleFactory
    {
        $decoratedFactory = $this->createMock(ExampleFactoryInterface::class);
        $decoratedFactory->method('create')->willReturn($taxon);

        return new TaxonExampleFactory(
            $decoratedFactory,
            $this->createImageFactory(),
            $this->createMock(ImageUploaderInterface::class),
            $this->createFileLocator(),
        );
    }

    /** @return FactoryInterface<ImageInterface> */
    private function createImageFactory(): FactoryInterface
    {
        $imageFactory = $this->createMock(FactoryInterface::class);
        $imageFactory->method('createNew')->willReturnCallback(static fn (): TaxonImage => new TaxonImage());

        return $imageFactory;
    }

    private function createFileLocator(): FileLocatorInterface
    {
        $fileLocator = $this->createMock(FileLocatorInterface::class);
        $fileLocator->method('locate')->willReturnArgument(0);

        return $fileLocator;
    }
}
