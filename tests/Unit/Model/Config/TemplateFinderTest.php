<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Config;

if (!class_exists(CollectionFactory::class)) {
    require_once __DIR__ . '/../../../Support/Stubs/Magento/Catalog/Model/ResourceModel/Category/CollectionFactory.php';
}

use ArrayIterator;
use Emico\CodeCept\Test\Unit;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\Registry;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2Tweakwise\Model\Config\TemplateFinder;
use Tweakwise\Magento2Tweakwise\Model\Config\Source\RecommendationOption;
use Tweakwise\Test\Support\UnitTester;

class TemplateFinderTest extends Unit
{
    protected UnitTester $tester;
    private Config $config;
    private Registry $registry;
    private CategoryRepository $categoryRepository;
    private CollectionFactory $categoryCollectionFactory;
    private TemplateFinder $subject;
    private array $capturedSelectedAttributes = [];
    private array $capturedPathFilter = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = $this->createMock(Config::class);
        $this->registry = $this->createMock(Registry::class);
        $this->categoryRepository = $this->createMock(CategoryRepository::class);
        $this->categoryCollectionFactory = $this->createMock(CollectionFactory::class);
        $this->subject = new TemplateFinder(
            $this->config,
            $this->registry,
            $this->categoryRepository,
            $this->categoryCollectionFactory,
        );
    }

    public function testForProductInheritsTemplateFromClosestAncestor(): void
    {
        $type = 'upsell';
        $attribute = 'tweakwise_upsell_template';
        $groupAttribute = 'tweakwise_upsell_group_code';
        $pathIds = [13, 10, 9, 2, 1];

        $product = $this->createMock(Product::class);
        $product->method('getData')->with($attribute)->willReturn(0);

        $currentCategory = $this->createPathCategory(array_reverse($pathIds));

        $this->registry->method('registry')->with('current_category')->willReturn($currentCategory);

        $collection = $this->createCategoryCollection(
            [
                $this->createLoadedCategory(13, [$attribute => 0, $groupAttribute => null]),
                $this->createLoadedCategory(10, [$attribute => 21, $groupAttribute => null]),
            ],
        );

        $this->categoryCollectionFactory->method('create')->willReturn($collection);
        $this->categoryRepository->expects($this->never())->method('get');

        $result = $this->subject->forProduct($product, $type);

        $this->assertSame(21, $result);
        $this->assertSame($pathIds, $this->capturedPathFilter);
        $this->assertEqualsCanonicalizing([$attribute, $groupAttribute], $this->capturedSelectedAttributes);
    }

    public function testForProductUsesDirectCategoryTemplateBeforeParent(): void
    {
        $type = 'crosssell';
        $attribute = 'tweakwise_crosssell_template';
        $groupAttribute = 'tweakwise_crosssell_group_code';
        $pathIds = [5, 2, 1];

        $product = $this->createMock(Product::class);
        $product->method('getData')->with($attribute)->willReturn(0);
        $product->method('getCategory')->willReturn($this->createPathCategory(array_reverse($pathIds)));
        $product->method('getCategoryIds')->willReturn([]);

        $this->registry->method('registry')->with('current_category')->willReturn(null);

        $collection = $this->createCategoryCollection(
            [
                $this->createLoadedCategory(5, [$attribute => 33, $groupAttribute => null]),
                $this->createLoadedCategory(2, [$attribute => 99, $groupAttribute => null]),
            ],
        );

        $this->categoryCollectionFactory->method('create')->willReturn($collection);

        $this->categoryRepository->expects($this->never())->method('get');

        $result = $this->subject->forProduct($product, $type);

        $this->assertSame(33, $result);
        $this->assertSame($pathIds, $this->capturedPathFilter);
        $this->assertEqualsCanonicalizing([$attribute, $groupAttribute], $this->capturedSelectedAttributes);
    }

    public function testForProductUsesProductLevelTemplateBeforeCategories(): void
    {
        $product = $this->createMock(Product::class);
        $product->expects($this->once())
            ->method('getData')
            ->with('tweakwise_upsell_template')
            ->willReturn(77);

        $this->registry->expects($this->never())->method('registry');
        $this->categoryCollectionFactory->expects($this->never())->method('create');
        $this->categoryRepository->expects($this->never())->method('get');

        $this->assertSame(77, $this->subject->forProduct($product, 'upsell'));
    }

    public function testForProductFallsBackToGlobalDefaultTemplateWhenUnsetEverywhere(): void
    {
        $type = 'upsell';

        $product = $this->createMock(Product::class);
        $product->method('getData')->with('tweakwise_upsell_template')->willReturn(0);
        $product->method('getCategory')->willReturn(null);
        $product->method('getCategoryIds')->willReturn([]);

        $this->registry->method('registry')->with('current_category')->willReturn(null);

        $this->config->expects($this->once())
            ->method('getRecommendationsTemplate')
            ->with($type)
            ->willReturn(19);

        $this->categoryCollectionFactory->expects($this->never())->method('create');
        $this->categoryRepository->expects($this->never())->method('get');

        $this->assertSame(19, $this->subject->forProduct($product, $type));
    }

    public function testForProductReturnsGroupCodeWhenAncestorUsesGroupOption(): void
    {
        $type = 'upsell';
        $attribute = 'tweakwise_upsell_template';
        $groupAttribute = 'tweakwise_upsell_group_code';
        $pathIds = [13, 10, 2, 1];

        $product = $this->createMock(Product::class);
        $product->method('getData')->with($attribute)->willReturn(0);
        $product->method('getCategory')->willReturn($this->createPathCategory(array_reverse($pathIds)));
        $product->method('getCategoryIds')->willReturn([]);

        $this->registry->method('registry')->with('current_category')->willReturn(null);

        $collection = $this->createCategoryCollection(
            [
                $this->createLoadedCategory(13, [$attribute => RecommendationOption::OPTION_CODE, $groupAttribute => 'upsell-group']),
            ],
        );

        $this->categoryCollectionFactory->method('create')->willReturn($collection);

        $result = $this->subject->forProduct($product, $type);

        $this->assertSame('upsell-group', $result);
        $this->assertSame($pathIds, $this->capturedPathFilter);
    }

    private function createCategoryCollection(array $categories): CategoryCollection
    {
        $this->capturedSelectedAttributes = [];
        $this->capturedPathFilter = [];

        $collection = $this->getMockBuilder(CategoryCollection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addAttributeToFilter', 'addAttributeToSelect', 'getIterator'])
            ->getMock();

        $collection->method('addAttributeToFilter')
            ->willReturnCallback(function (string $attribute, array $condition) use ($collection): CategoryCollection {
                if ($attribute === 'entity_id') {
                    $this->capturedPathFilter = $condition['in'] ?? [];
                }

                return $collection;
            });

        $collection->method('addAttributeToSelect')
            ->willReturnCallback(function ($attribute) use ($collection): CategoryCollection {
                foreach ((array) $attribute as $selectedAttribute) {
                    $this->capturedSelectedAttributes[] = (string) $selectedAttribute;
                }

                return $collection;
            });

        $collection->method('getIterator')->willReturn(new ArrayIterator($categories));

        return $collection;
    }

    private function createPathCategory(array $pathIds): Category
    {
        $category = $this->createMock(Category::class);
        $category->method('getPathIds')->willReturn($pathIds);

        return $category;
    }

    private function createLoadedCategory(int $id, array $attributes): Category
    {
        $category = $this->createMock(Category::class);
        $category->method('getId')->willReturn($id);
        $category->method('getData')->willReturnCallback(
            static fn (string $attribute): int|string|null => $attributes[$attribute] ?? null,
        );

        return $category;
    }
}
