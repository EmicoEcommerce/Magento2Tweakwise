<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Catalog\Layer;

use Emico\CodeCept\Test\Unit;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Layer\ItemCollectionProviderInterface;
use Mockery;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\ItemCollectionProvider;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\NavigationContext;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Product\Collection;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Product\CollectionFactory;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\ProductNavigationRequest;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2TweakwiseExport\Model\Logger;
use Tweakwise\Test\Support\UnitTester;

class ItemCollectionProviderTest extends Unit
{
    protected UnitTester $tester;

    protected function _after(): void
    {
        Mockery::close();
    }

    public function testCategoryFilterIsAddedBeforeCreatingTweakwiseCollection(): void
    {
        $category = Mockery::mock(Category::class);
        $request = Mockery::mock(ProductNavigationRequest::class);
        $request->shouldReceive('hasParameter')->once()->with('tn_cid')->andReturn(false);
        $request->shouldReceive('addCategoryFilter')->once()->with($category)->andReturnSelf();

        $navigationContext = Mockery::mock(NavigationContext::class);
        $navigationContext->shouldReceive('getRequest')->andReturn($request);

        $config = Mockery::mock(Config::class);
        $config->shouldReceive('isLayeredEnabled')->once()->andReturn(true);
        $config->shouldReceive('isSearchEnabled')->once()->andReturn(true);
        $config->shouldReceive('getTweakwiseExceptionTrown')->once()->andReturn(false);

        $collection = Mockery::mock(Collection::class);
        $collectionFactory = Mockery::mock(CollectionFactory::class);
        $collectionFactory
            ->shouldReceive('create')
            ->once()
            ->with(['navigationContext' => $navigationContext])
            ->andReturn($collection);

        $subject = new ItemCollectionProvider(
            $config,
            Mockery::mock(Logger::class),
            Mockery::mock(ItemCollectionProviderInterface::class),
            $collectionFactory,
            $navigationContext
        );

        $this->assertSame($collection, $subject->getCollection($category));
    }
}
