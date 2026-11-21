<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Autocomplete;

use Emico\CodeCept\Test\Unit;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Config as CatalogConfig;
use Magento\Catalog\Model\Layer\Category\CollectionFilter;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Search\Model\QueryFactory;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Mockery;
use Tweakwise\Magento2Tweakwise\Model\Autocomplete\DataProvider\ProductItem;
use Tweakwise\Magento2Tweakwise\Model\Autocomplete\DataProvider\ProductItemFactory;
use Tweakwise\Magento2Tweakwise\Model\Autocomplete\DataProviderHelper;
use Tweakwise\Magento2Tweakwise\Model\Client\Request;
use Tweakwise\Magento2Tweakwise\Model\Client\Response\Suggestions\ProductSuggestionsResponse;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2TweakwiseExport\Model\Helper;

class DataProviderHelperTest extends Unit
{
    private const ROOT_CATEGORY_ID = 2;

    public function testProductsOfAGroupedResponseAreReturnedOnce(): void
    {
        $response = $this->createResponse([
            'block' => [
                'title' => 'Products',
                'groups' => [
                    'group' => [
                        ['code' => '100011', 'items' => ['item' => $this->createItem('100011')]],
                        ['code' => '100012', 'items' => ['item' => $this->createItem('100012')]],
                    ],
                ],
            ],
        ]);

        $items = $this->createHelper([1, 2])->getProductItems($response);

        $this->assertSame([1, 2], $this->getProductIds($items));
    }

    private function createHelper(array $productIds): DataProviderHelper
    {
        $store = Mockery::mock(Store::class);
        $store->shouldReceive('getRootCategoryId')->andReturn(self::ROOT_CATEGORY_ID);
        $storeManager = Mockery::mock(StoreManagerInterface::class);
        $storeManager->shouldReceive('getStore')->andReturn($store);

        $category = Mockery::mock(Category::class);
        $category->shouldReceive('getId')->andReturn(self::ROOT_CATEGORY_ID);
        $categoryRepository = Mockery::mock(CategoryRepositoryInterface::class);
        $categoryRepository->shouldReceive('get')->with(self::ROOT_CATEGORY_ID)->andReturn($category);

        $request = Mockery::mock(HttpRequest::class);
        $request->shouldReceive('getParam')->with('cid')->andReturn(null);

        $catalogConfig = Mockery::mock(CatalogConfig::class);
        $catalogConfig->shouldReceive('getProductAttributes')->andReturn([]);

        $productItemFactory = Mockery::mock(ProductItemFactory::class);
        $productItemFactory->shouldReceive('create')->andReturnUsing(
            static function (array $data): ProductItem {
                $item = Mockery::mock(ProductItem::class);
                $item->shouldReceive('getProduct')->andReturn($data['product']);

                return $item;
            }
        );

        return new DataProviderHelper(
            Mockery::mock(Config::class),
            Mockery::mock(QueryFactory::class),
            $storeManager,
            $categoryRepository,
            $request,
            $this->createCollectionFactory($productIds),
            Mockery::mock(CollectionFilter::class),
            $productItemFactory,
            $catalogConfig
        );
    }

    private function createCollectionFactory(array $productIds): CollectionFactory
    {
        $products = [];
        foreach ($productIds as $productId) {
            $product = Mockery::mock(Product::class)->makePartial();
            $product->setId($productId);
            $products[$productId] = $product;
        }

        $collection = Mockery::mock(Collection::class);
        $collection->shouldReceive(
            'setStore',
            'addAttributeToFilter',
            'addFieldToFilter',
            'addAttributeToSelect',
            'addMinimalPrice',
            'addFinalPrice',
            'addTaxPercents',
            'addUrlRewrite',
            'addCategoryFilter'
        )->andReturnSelf();
        $collection->shouldReceive('getItemById')->andReturnUsing(
            static fn (int $productId): ?Product => $products[$productId] ?? null
        );

        $collectionFactory = Mockery::mock(CollectionFactory::class);
        $collectionFactory->shouldReceive('create')->andReturn($collection);

        return $collectionFactory;
    }

    private function createResponse(array $blocks): ProductSuggestionsResponse
    {
        $helper = Mockery::mock(Helper::class);
        $helper->shouldReceive('getStoreId')->andReturnUsing(
            static fn (int $tweakwiseId): int => (int)substr((string)$tweakwiseId, 5)
        );

        return new ProductSuggestionsResponse($helper, Mockery::mock(Request::class), ['blocks' => $blocks]);
    }

    private function createItem(string $tweakwiseId): array
    {
        return ['itemno' => $tweakwiseId, 'title' => 'Product ' . $tweakwiseId, 'type' => 'product', 'price' => 10];
    }

    private function getProductIds(array $items): array
    {
        return array_map(static fn (ProductItem $item): int => (int)$item->getProduct()->getId(), $items);
    }
}
