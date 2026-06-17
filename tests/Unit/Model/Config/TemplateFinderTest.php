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
use Tweakwise\Test\Support\UnitTester;

class TemplateFinderTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var Registry
     */
    private Registry $registry;

    /**
     * @var CategoryRepository
     */
    private CategoryRepository $categoryRepository;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $categoryCollectionFactory;

    /**
     * @var TemplateFinder
     */
    private TemplateFinder $templateFinder;

    /**
     * @var string[]
     */
    private array $capturedSelectedAttributes = [];

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->config = $this->createMock(Config::class);
        $this->registry = $this->createMock(Registry::class);
        $this->categoryRepository = $this->createMock(CategoryRepository::class);
        $this->categoryCollectionFactory = $this->createMock(CollectionFactory::class);
        $this->templateFinder = new TemplateFinder(
            $this->config,
            $this->registry,
            $this->categoryRepository,
            $this->categoryCollectionFactory,
        );
    }

    /**
     * @return void
     */
    public function testForProductInheritsTemplateFromClosestAncestor(): void
    {
        $type = 'upsell';
        $attribute = 'tweakwise_upsell_template';
        $groupAttribute = 'tweakwise_upsell_group_code';
        $pathIds = [13, 10, 9, 2, 1];

        $product = $this->createMock(Product::class);
        $product->method('getData')->with($attribute)->willReturn(0);
        $product->expects($this->never())->method('getCategory');
        $product->expects($this->never())->method('getCategoryIds');

        $currentCategory = $this->createMock(Category::class);
        $currentCategory->expects($this->once())->method('getPathIds')->willReturn(array_reverse($pathIds));
        $currentCategory->expects($this->never())->method('getParentCategory');

        $this->registry->expects($this->once())
            ->method('registry')
            ->with('current_category')
            ->willReturn($currentCategory);

        $collection = $this->createCategoryCollection(
            [
                $this->createLoadedCategory(13, [$attribute => 0, $groupAttribute => null]),
                $this->createLoadedCategory(10, [$attribute => 21, $groupAttribute => null]),
            ],
        );

        $collection->expects($this->once())
            ->method('addAttributeToFilter')
            ->with('entity_id', ['in' => $pathIds])
            ->willReturnSelf();

        $this->categoryCollectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($collection);

        $result = $this->templateFinder->forProduct($product, $type);

        $this->assertSame(21, $result);
        $this->assertEqualsCanonicalizing([$attribute, $groupAttribute], $this->capturedSelectedAttributes);
    }

    /**
     * @return void
     */
    public function testForProductUsesDirectCategoryTemplateBeforeParent(): void
    {
        $type = 'crosssell';
        $attribute = 'tweakwise_crosssell_template';
        $groupAttribute = 'tweakwise_crosssell_group_code';
        $pathIds = [5, 2, 1];

        $product = $this->createMock(Product::class);
        $product->method('getData')->with($attribute)->willReturn(0);
        $product->method('getCategory')->willReturn($this->createPathCategory(array_reverse($pathIds)));
        $product->expects($this->never())->method('getCategoryIds');

        $this->registry->expects($this->once())
            ->method('registry')
            ->with('current_category')
            ->willReturn(null);

        $collection = $this->createCategoryCollection(
            [
                $this->createLoadedCategory(5, [$attribute => 33, $groupAttribute => null]),
                $this->createLoadedCategory(2, [$attribute => 99, $groupAttribute => null]),
            ],
        );

        $collection->expects($this->once())
            ->method('addAttributeToFilter')
            ->with('entity_id', ['in' => $pathIds])
            ->willReturnSelf();

        $this->categoryCollectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($collection);

        $this->categoryRepository->expects($this->never())->method('get');

        $result = $this->templateFinder->forProduct($product, $type);

        $this->assertSame(33, $result);
        $this->assertEqualsCanonicalizing([$attribute, $groupAttribute], $this->capturedSelectedAttributes);
    }

    /**
     * @return void
     */
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

        $this->assertSame(77, $this->templateFinder->forProduct($product, 'upsell'));
    }

    /**
     * @return void
     */
    public function testForProductFallsBackToGlobalDefaultTemplateWhenUnsetEverywhere(): void
    {
        $type = 'upsell';

        $product = $this->createMock(Product::class);
        $product->method('getData')->with('tweakwise_upsell_template')->willReturn(0);
        $product->method('getCategory')->willReturn(null);
        $product->method('getCategoryIds')->willReturn([]);

        $this->registry->expects($this->once())
            ->method('registry')
            ->with('current_category')
            ->willReturn(null);

        $this->config->expects($this->once())
            ->method('getRecommendationsTemplate')
            ->with($type)
            ->willReturn(19);

        $this->categoryCollectionFactory->expects($this->never())->method('create');
        $this->categoryRepository->expects($this->never())->method('get');

        $this->assertSame(19, $this->templateFinder->forProduct($product, $type));
    }

    /**
     * @param Category[] $categories
     * @return CategoryCollection
     */
    private function createCategoryCollection(array $categories): CategoryCollection
    {
        $this->capturedSelectedAttributes = [];
        $collection = $this->getMockBuilder(CategoryCollection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addAttributeToFilter', 'addAttributeToSelect', 'getIterator'])
            ->getMock();

        $collection->expects($this->exactly(2))
            ->method('addAttributeToSelect')
            ->willReturnCallback(function (string $attribute) use ($collection): CategoryCollection {
                $this->capturedSelectedAttributes[] = $attribute;
                return $collection;
            });

        $collection->method('getIterator')->willReturn(new ArrayIterator($categories));

        return $collection;
    }

    /**
     * @param int[] $pathIds
     * @return Category
     */
    private function createPathCategory(array $pathIds): Category
    {
        $category = $this->createMock(Category::class);
        $category->expects($this->once())->method('getPathIds')->willReturn($pathIds);

        return $category;
    }

    /**
     * @param int $id
     * @param array<string, int|string|null> $attributes
     * @return Category
     */
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
