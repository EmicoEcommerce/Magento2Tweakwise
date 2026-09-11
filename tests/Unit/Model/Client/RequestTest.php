<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Client;

use Emico\CodeCept\Test\Unit;
use Magento\Catalog\Model\Category;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use Mockery;
use Tweakwise\Magento2Tweakwise\Model\Client\Request;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2TweakwiseExport\Model\Helper;

class RequestTest extends Unit
{
    private const STORE_ID = 1;

    /**
     * getTweakwiseId prefixes the store id, e.g. store 1 + category 2 => "100012".
     */
    private function createRequest(bool $categoryViewDefault = false): Request
    {
        $store = Mockery::mock(StoreInterface::class);
        $store->shouldReceive('getId')->andReturn(self::STORE_ID);

        $storeManager = Mockery::mock(StoreManager::class);
        $storeManager->shouldReceive('getStore')->andReturn($store);

        $helper = Mockery::mock(Helper::class);
        $helper->shouldReceive('getTweakwiseId')
            ->andReturnUsing(
                static fn (int $storeId, int $categoryId): string => '1' . str_pad((string) $storeId, 4, '0', STR_PAD_LEFT) . $categoryId
            );

        $config = Mockery::mock(Config::class);
        $config->shouldReceive('isCategoryViewDefault')->andReturn($categoryViewDefault);

        return new Request($helper, $storeManager, $config);
    }

    /**
     * Regression: root category 1 maps to an empty string, so imploding the path
     * with "-" used to produce a leading dash ("-100012"), which the Tweakwise
     * gateway rejects with a 400.
     */
    public function testAddCategoryPathFilterDropsRootCategoryPlaceholder(): void
    {
        $request = $this->createRequest();

        $request->addCategoryPathFilter([1, 2]);

        $this->assertSame('100012', $request->getParameter('tn_cid'));
    }

    public function testAddCategoryPathFilterKeepsMultiLevelPath(): void
    {
        $request = $this->createRequest();

        $request->addCategoryPathFilter([3, 45]);

        $this->assertSame('100013-1000145', $request->getParameter('tn_cid'));
    }

    /**
     * End-to-end of the production bug: outside a category context the filter list
     * scopes on the store root category (parent id 1) and, with "default category
     * view" enabled, builds the path [1, 2]. The resulting tn_cid must not start
     * with a dash.
     */
    public function testAddCategoryFilterForRootCategoryWithCategoryViewDefault(): void
    {
        $request = $this->createRequest(categoryViewDefault: true);

        $store = Mockery::mock(Store::class);
        $store->shouldReceive('getRootCategoryId')->andReturn(2);

        $rootCategory = Mockery::mock(Category::class);
        $rootCategory->shouldReceive('getParentId')->andReturn(1);
        $rootCategory->shouldReceive('getId')->andReturn(2);
        $rootCategory->shouldReceive('getStore')->andReturn($store);

        $request->addCategoryFilter($rootCategory);

        $this->assertSame('100012', $request->getParameter('tn_cid'));
    }
}
