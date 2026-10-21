<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Catalog\Layer;

use Emico\CodeCept\Models\Fixtures\CategoryFixture;
use Emico\CodeCept\Models\Fixtures\ProductFixture;
use Emico\CodeCept\Test\Unit;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection;
use Mockery;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\ItemCollectionProvider;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\NavigationContext;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\ProductNavigationRequest;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\ProductSearchRequest;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Test\Support\UnitTester;

class NativeFallbackTest extends Unit
{
    protected UnitTester $tester;

    /**
     * @dataProvider pageTypes
     */
    public function testNativeCollectionReturnsProductsAfterAnApiFailure(bool $search): void
    {
        $objectManager = $this->tester->getObjectManager();
        $categoryFixture = $objectManager->create(CategoryFixture::class, ['name' => 'Outage fallback category']);
        $this->tester->createFixture($categoryFixture);
        $product = $objectManager->create(ProductFixture::class, ['sku' => 'tw-outage-fallback-product']);
        $product->assignToCategory($categoryFixture);
        $this->tester->createFixture($product);
        $category = $objectManager->get(CategoryRepositoryInterface::class)->get($categoryFixture->getId());

        $config = Mockery::mock(Config::class);
        $config->shouldReceive('isLayeredEnabled')->andReturn(false);
        $config->shouldReceive('isSearchEnabled')->andReturn(true);
        $config->shouldReceive('getTweakwiseExceptionTrown')->andReturn(true);
        $context = Mockery::mock(NavigationContext::class);
        $context->shouldReceive('getRequest')->andReturn(
            Mockery::mock($search ? ProductSearchRequest::class : ProductNavigationRequest::class)
        );
        $context->shouldNotReceive('getResponse');

        $type = ItemCollectionProvider::class . ($search ? '\Search' : '\Category');
        $provider = $objectManager->create($type, [
            'config' => $config,
            'navigationContext' => $context,
        ]);
        $collection = $provider->getCollection($category);
        $this->assertInstanceOf(Collection::class, $collection);
        if ($search) {
            $collection->addSearchFilter($product->getSku());
        }

        $skus = array_map(static fn ($item): string => $item->getSku(), $collection->getItems());
        $this->assertContains($product->getSku(), $skus);
    }

    public static function pageTypes(): array
    {
        return ['category' => [false], 'search' => [true]];
    }
}
