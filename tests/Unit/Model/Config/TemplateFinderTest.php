<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Config;

use ArrayIterator;
use Emico\CodeCept\Test\Unit;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\Registry;
use Mockery;
use Mockery\MockInterface;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2Tweakwise\Model\Config\TemplateFinder;
use Tweakwise\Magento2Tweakwise\Model\Config\Source\RecommendationOption;
use Tweakwise\Test\Support\UnitTester;

class TemplateFinderTest extends Unit
{
    protected UnitTester $tester;
    private Config&MockInterface $config;
    private Registry&MockInterface $registry;
    private CategoryRepository&MockInterface $categoryRepository;
    private CollectionFactory&MockInterface $categoryCollectionFactory;
    private TemplateFinder $subject;
    private array $capturedSelectedAttributes = [];
    private array $capturedPathFilter = [];

    public function _before(): void
    {
        $this->config = Mockery::mock(Config::class);
        $this->registry = Mockery::mock(Registry::class);
        $this->categoryRepository = Mockery::mock(CategoryRepository::class);
        $this->categoryCollectionFactory = Mockery::mock(CollectionFactory::class);
        $this->subject = new TemplateFinder(
            $this->config,
            $this->registry,
            $this->categoryRepository,
            $this->categoryCollectionFactory,
        );
    }

    public function _after(): void
    {
        Mockery::close();
    }

    public function testForProductInheritsTemplateFromClosestAncestor(): void
    {
        $type = 'upsell';
        $attribute = 'tweakwise_upsell_template';
        $groupAttribute = 'tweakwise_upsell_group_code';
        $pathIds = [13, 10, 9, 2, 1];

        $product = Mockery::mock(Product::class);
        $product->shouldReceive('getData')
            ->with($attribute)
            ->andReturn(0);

        $currentCategory = $this->createPathCategory(array_reverse($pathIds));

        $this->registry->shouldReceive('registry')
            ->with('current_category')
            ->andReturn($currentCategory);

        $collection = $this->createCategoryCollection(
            [
                $this->createLoadedCategory(13, [$attribute => 0, $groupAttribute => null]),
                $this->createLoadedCategory(10, [$attribute => 21, $groupAttribute => null]),
            ],
        );

        $this->categoryCollectionFactory->shouldReceive('create')->andReturn($collection);
        $this->categoryRepository->shouldNotReceive('get');

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

        $product = Mockery::mock(Product::class);
        $product->shouldReceive('getData')
            ->with($attribute)
            ->andReturn(0);
        $product->shouldReceive('getCategory')->andReturn($this->createPathCategory(array_reverse($pathIds)));
        $product->shouldReceive('getCategoryIds')->andReturn([]);

        $this->registry->shouldReceive('registry')
            ->with('current_category')
            ->andReturn(null);

        $collection = $this->createCategoryCollection(
            [
                $this->createLoadedCategory(5, [$attribute => 33, $groupAttribute => null]),
                $this->createLoadedCategory(2, [$attribute => 99, $groupAttribute => null]),
            ],
        );

        $this->categoryCollectionFactory->shouldReceive('create')->andReturn($collection);
        $this->categoryRepository->shouldNotReceive('get');

        $result = $this->subject->forProduct($product, $type);

        $this->assertSame(33, $result);
        $this->assertSame($pathIds, $this->capturedPathFilter);
        $this->assertEqualsCanonicalizing([$attribute, $groupAttribute], $this->capturedSelectedAttributes);
    }

    public function testForProductUsesProductLevelTemplateBeforeCategories(): void
    {
        $product = Mockery::mock(Product::class);
        $product->shouldReceive('getData')->andReturnUsing(
            static fn (string $attribute): int => $attribute === 'tweakwise_upsell_template' ? 77 : 0,
        );

        $this->registry->shouldNotReceive('registry');
        $this->categoryCollectionFactory->shouldNotReceive('create');
        $this->categoryRepository->shouldNotReceive('get');

        $this->assertSame(77, $this->subject->forProduct($product, 'upsell'));
    }

    public function testForProductFallsBackToGlobalDefaultTemplateWhenUnsetEverywhere(): void
    {
        $type = 'upsell';

        $product = Mockery::mock(Product::class);
        $product->shouldReceive('getData')
            ->with('tweakwise_upsell_template')
            ->andReturn(0);
        $product->shouldReceive('getCategory')->andReturn(null);
        $product->shouldReceive('getCategoryIds')->andReturn([]);

        $this->registry->shouldReceive('registry')
            ->with('current_category')
            ->andReturn(null);

        $this->config->shouldReceive('getRecommendationsTemplate')
            ->with($type)
            ->andReturn(19);

        $this->categoryCollectionFactory->shouldNotReceive('create');
        $this->categoryRepository->shouldNotReceive('get');

        $this->assertSame(19, $this->subject->forProduct($product, $type));
    }

    public function testForProductReturnsGroupCodeWhenAncestorUsesGroupOption(): void
    {
        $type = 'upsell';
        $attribute = 'tweakwise_upsell_template';
        $groupAttribute = 'tweakwise_upsell_group_code';
        $pathIds = [13, 10, 2, 1];

        $product = Mockery::mock(Product::class);
        $product->shouldReceive('getData')
            ->with($attribute)
            ->andReturn(0);
        $product->shouldReceive('getCategory')->andReturn($this->createPathCategory(array_reverse($pathIds)));
        $product->shouldReceive('getCategoryIds')->andReturn([]);

        $this->registry->shouldReceive('registry')
            ->with('current_category')
            ->andReturn(null);

        $collection = $this->createCategoryCollection(
            [
                $this->createLoadedCategory(13, [$attribute => RecommendationOption::OPTION_CODE, $groupAttribute => 'upsell-group']),
            ],
        );

        $this->categoryCollectionFactory->shouldReceive('create')->andReturn($collection);

        $result = $this->subject->forProduct($product, $type);

        $this->assertSame('upsell-group', $result);
        $this->assertSame($pathIds, $this->capturedPathFilter);
    }

    private function createCategoryCollection(array $categories): CategoryCollection
    {
        $this->capturedSelectedAttributes = [];
        $this->capturedPathFilter = [];

        $collection = Mockery::mock(CategoryCollection::class);

        $collection->shouldReceive('addAttributeToFilter')
            ->andReturnUsing(function (string $attribute, array $condition) use ($collection): CategoryCollection {
                if ($attribute === 'entity_id') {
                    $this->capturedPathFilter = $condition['in'] ?? [];
                }

                return $collection;
            });

        $collection->shouldReceive('addAttributeToSelect')
            ->andReturnUsing(function ($attribute) use ($collection): CategoryCollection {
                foreach ((array) $attribute as $selectedAttribute) {
                    $this->capturedSelectedAttributes[] = (string) $selectedAttribute;
                }

                return $collection;
            });

        $collection->shouldReceive('getIterator')->andReturn(new ArrayIterator($categories));

        return $collection;
    }

    private function createPathCategory(array $pathIds): Category
    {
        $category = Mockery::mock(Category::class);
        $category->shouldReceive('getPathIds')->andReturn($pathIds);

        return $category;
    }

    private function createLoadedCategory(int $id, array $attributes): Category
    {
        $category = Mockery::mock(Category::class);
        $category->shouldReceive('getId')->andReturn($id);
        $category->shouldReceive('getData')->andReturnUsing(
            static fn (string $attribute): int|string|null => $attributes[$attribute] ?? null,
        );

        return $category;
    }
}
