<?php

declare(strict_types=1);

namespace Webgriffe\SyliusFixturesPlugin\Fixture\Factory;

use Sylius\Bundle\CoreBundle\Fixture\Factory\ExampleFactoryInterface;
use Sylius\Component\Core\Model\ImageInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Core\Uploader\ImageUploaderInterface;
use Sylius\Resource\Factory\FactoryInterface;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Webmozart\Assert\Assert;

/**
 * Adds images to the Sylius taxon example factory, which does not handle them at all.
 *
 * The factory of Sylius creates the whole tree at once, so the images of the children are collected by code
 * from the options and applied to the tree it returns.
 *
 * @implements ExampleFactoryInterface<TaxonInterface>
 */
final readonly class TaxonExampleFactory implements ExampleFactoryInterface
{
    /**
     * @param ExampleFactoryInterface<TaxonInterface> $decoratedFactory
     * @param FactoryInterface<ImageInterface> $taxonImageFactory
     */
    public function __construct(
        private ExampleFactoryInterface $decoratedFactory,
        private FactoryInterface $taxonImageFactory,
        private ImageUploaderInterface $imageUploader,
        private FileLocatorInterface $fileLocator,
    ) {
    }

    /** @param array<string, mixed> $options */
    #[\Override]
    public function create(array $options = []): TaxonInterface
    {
        $imagesByCode = [];
        $options = $this->extractImages($options, $imagesByCode);

        $taxon = $this->decoratedFactory->create($options);

        $this->applyImages($taxon, $imagesByCode);

        return $taxon;
    }

    /**
     * @param array<string, mixed> $options
     * @param array<string, list<array<string, string>>> $imagesByCode
     *
     * @return array<string, mixed> the options without the images, which the Sylius factory would reject
     */
    private function extractImages(array $options, array &$imagesByCode): array
    {
        if (isset($options['code'], $options['images'])) {
            $code = $options['code'];
            Assert::string($code);
            /** @var list<array<string, string>> $images */
            $images = $options['images'];

            $imagesByCode[$code] = $images;
            unset($options['images']);
        }

        if (!isset($options['children'])) {
            return $options;
        }

        Assert::isArray($options['children']);
        foreach ($options['children'] as $key => $child) {
            Assert::isArray($child);
            $options['children'][$key] = $this->extractImages($child, $imagesByCode);
        }

        return $options;
    }

    /** @param array<string, list<array<string, string>>> $imagesByCode */
    private function applyImages(TaxonInterface $taxon, array $imagesByCode): void
    {
        foreach ($imagesByCode[(string) $taxon->getCode()] ?? [] as $image) {
            $this->addImage($taxon, $image);
        }

        foreach ($taxon->getChildren() as $child) {
            Assert::isInstanceOf($child, TaxonInterface::class);

            $this->applyImages($child, $imagesByCode);
        }
    }

    /** @param array<string, string> $image */
    private function addImage(TaxonInterface $taxon, array $image): void
    {
        $path = $this->fileLocator->locate($image['path']);
        Assert::string($path);

        $taxonImage = $this->taxonImageFactory->createNew();
        $taxonImage->setFile(new UploadedFile($path, basename($path)));
        $taxonImage->setType($image['type'] ?? null);

        $this->imageUploader->upload($taxonImage);

        $taxon->addImage($taxonImage);
    }
}
