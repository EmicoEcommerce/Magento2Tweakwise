<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Catalog\Layer\Url;

use Emico\CodeCept\Test\Unit;
use ArrayIterator;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\CatalogUrlRewrite\Model\CategoryUrlRewriteGenerator;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use Mockery;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Filter\Item;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\StrategyHelper;
use Tweakwise\Magento2TweakwiseExport\Model\Helper as ExportHelper;
use Tweakwise\Test\Support\UnitTester;

class StrategyHelperTest extends Unit
{
    protected UnitTester $tester;

    /**
     * @return void
     * phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
     */
    public function _after(): void
    {
        Mockery::close();
    }

    /**
     * @return void
     */
    public function testWarmUpLoadsCategoriesAndPreloadsRewritesForNestedItems(): void
    {
        $categoryOne = Mockery::mock(CategoryInterface::class);
        $categoryOne->shouldReceive('getId')->andReturn(10);
        $categoryOne->shouldReceive('getData')->with('url')->andReturn(null);
        $categoryOne->shouldReceive('setData')->with('request_path', 'women/tops.html')->once();

        $categoryTwo = Mockery::mock(CategoryInterface::class);
        $categoryTwo->shouldReceive('getId')->andReturn(20);
        $categoryTwo->shouldReceive('getData')->with('url')->andReturn(null);
        $categoryTwo->shouldReceive('setData')->with('request_path', 'women/jackets.html')->once();

        $collection = Mockery::mock('Magento\\Catalog\\Model\\ResourceModel\\Category\\Collection');
        $collection->shouldReceive('setStoreId')->with(3)->once()->andReturnSelf();
        $collection->shouldReceive('addAttributeToSelect')->with(['name', 'url_key', 'url_path', 'is_active'])->once()->andReturnSelf();
        $collection->shouldReceive('addFieldToFilter')->with('entity_id', ['in' => [10, 20]])->once()->andReturnSelf();
        $collection->shouldReceive('load')->once()->andReturnSelf();
        $collection->shouldReceive('getIterator')->andReturn(new ArrayIterator([$categoryOne, $categoryTwo]));

        $categoryCollectionFactory = Mockery::mock(CategoryCollectionFactory::class);
        $categoryCollectionFactory->shouldReceive('create')->once()->andReturn($collection);

        $rewriteOne = Mockery::mock(UrlRewrite::class);
        $rewriteOne->shouldReceive('getEntityId')->andReturn(10);
        $rewriteOne->shouldReceive('getRequestPath')->andReturn('women/tops.html');

        $rewriteTwo = Mockery::mock(UrlRewrite::class);
        $rewriteTwo->shouldReceive('getEntityId')->andReturn(20);
        $rewriteTwo->shouldReceive('getRequestPath')->andReturn('women/jackets.html');

        $urlFinder = Mockery::mock(UrlFinderInterface::class);
        $urlFinder->shouldReceive('findAllByData')
            ->once()
            ->with([
                UrlRewrite::ENTITY_ID => [10, 20],
                UrlRewrite::ENTITY_TYPE => CategoryUrlRewriteGenerator::ENTITY_TYPE,
                UrlRewrite::STORE_ID => 3,
                UrlRewrite::REDIRECT_TYPE => 0,
            ])
            ->andReturn([$rewriteOne, $rewriteTwo]);

        $exportHelper = Mockery::mock(ExportHelper::class);
        $exportHelper->shouldReceive('getStoreId')->with(100)->andReturn(10);
        $exportHelper->shouldReceive('getStoreId')->with(200)->andReturn(20);

        $helper = new StrategyHelper(
            $exportHelper,
            Mockery::mock(CategoryRepositoryInterface::class),
            Mockery::mock(StoreManagerInterface::class),
            $categoryCollectionFactory,
            $urlFinder
        );

        $childAttribute = Mockery::mock();
        $childAttribute->shouldReceive('getAttributeId')->zeroOrMoreTimes()->andReturn(200);
        $childItem = Mockery::mock(Item::class);
        $childItem->shouldReceive('getAttribute')->zeroOrMoreTimes()->andReturn($childAttribute);
        $childItem->shouldReceive('getChildren')->zeroOrMoreTimes()->andReturn([]);

        $parentAttribute = Mockery::mock();
        $parentAttribute->shouldReceive('getAttributeId')->zeroOrMoreTimes()->andReturn(100);
        $parentItem = Mockery::mock(Item::class);
        $parentItem->shouldReceive('getAttribute')->zeroOrMoreTimes()->andReturn($parentAttribute);
        $parentItem->shouldReceive('getChildren')->zeroOrMoreTimes()->andReturn([$childItem]);

        $helper->warmUp([$parentItem], 3);

        $this->assertTrue(true);
    }

    /**
     * @return void
     */
    public function testGetCategoryFromItemReturnsCategoryFromCacheAfterWarmUp(): void
    {
        $cachedCategory = Mockery::mock(CategoryInterface::class);
        $cachedCategory->shouldReceive('getId')->andReturn(10);
        $cachedCategory->shouldReceive('getData')->with('url')->andReturn(null);
        $cachedCategory->shouldReceive('setData')->with('request_path', 'women/tops.html')->once();

        $collection = Mockery::mock('Magento\\Catalog\\Model\\ResourceModel\\Category\\Collection');
        $collection->shouldReceive('setStoreId')->with(5)->once()->andReturnSelf();
        $collection->shouldReceive('addAttributeToSelect')->with(['name', 'url_key', 'url_path', 'is_active'])->once()->andReturnSelf();
        $collection->shouldReceive('addFieldToFilter')->with('entity_id', ['in' => [10]])->once()->andReturnSelf();
        $collection->shouldReceive('load')->once()->andReturnSelf();
        $collection->shouldReceive('getIterator')->andReturn(new ArrayIterator([$cachedCategory]));

        $categoryCollectionFactory = Mockery::mock(CategoryCollectionFactory::class);
        $categoryCollectionFactory->shouldReceive('create')->once()->andReturn($collection);

        $rewrite = Mockery::mock(UrlRewrite::class);
        $rewrite->shouldReceive('getEntityId')->andReturn(10);
        $rewrite->shouldReceive('getRequestPath')->andReturn('women/tops.html');

        $urlFinder = Mockery::mock(UrlFinderInterface::class);
        $urlFinder->shouldReceive('findAllByData')->once()->andReturn([$rewrite]);

        $store = Mockery::mock();
        $store->shouldReceive('getId')->once()->andReturn(5);

        $storeManager = Mockery::mock(StoreManagerInterface::class);
        $storeManager->shouldReceive('getStore')->once()->andReturn($store);

        $categoryRepository = Mockery::mock(CategoryRepositoryInterface::class);
        $categoryRepository->shouldNotReceive('get');

        $exportHelper = Mockery::mock(ExportHelper::class);
        $exportHelper->shouldReceive('getStoreId')->with(100)->andReturn(10);

        $helper = new StrategyHelper(
            $exportHelper,
            $categoryRepository,
            $storeManager,
            $categoryCollectionFactory,
            $urlFinder
        );

        $attribute = Mockery::mock();
        $attribute->shouldReceive('getAttributeId')->zeroOrMoreTimes()->andReturn(100);
        $item = Mockery::mock(Item::class);
        $item->shouldReceive('getAttribute')->zeroOrMoreTimes()->andReturn($attribute);
        $item->shouldReceive('getChildren')->zeroOrMoreTimes()->andReturn([]);

        $helper->warmUp([$item], 5);

        $result = $helper->getCategoryFromItem($item);

        $this->assertSame($cachedCategory, $result);
    }

    /**
     * @return void
     */
    public function testGetCategoryFromItemFallsBackToNullStoreIdWhenStoreIsUnavailable(): void
    {
        $category = Mockery::mock(CategoryInterface::class);

        $categoryRepository = Mockery::mock(CategoryRepositoryInterface::class);
        $categoryRepository->shouldReceive('get')->once()->with(42, null)->andReturn($category);

        $storeManager = Mockery::mock(StoreManagerInterface::class);
        $storeManager->shouldReceive('getStore')->once()->andThrow(new NoSuchEntityException(__('No such entity')));

        $exportHelper = Mockery::mock(ExportHelper::class);
        $exportHelper->shouldReceive('getStoreId')->with(100)->andReturn(42);

        $helper = new StrategyHelper(
            $exportHelper,
            $categoryRepository,
            $storeManager,
            Mockery::mock(CategoryCollectionFactory::class),
            Mockery::mock(UrlFinderInterface::class)
        );

        $attribute = Mockery::mock();
        $attribute->shouldReceive('getAttributeId')->once()->andReturn(100);
        $item = Mockery::mock(Item::class);
        $item->shouldReceive('getAttribute')->once()->andReturn($attribute);

        $result = $helper->getCategoryFromItem($item);

        $this->assertSame($category, $result);
    }
}
