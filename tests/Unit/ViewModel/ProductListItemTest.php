<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\ViewModel;

use Emico\CodeCept\Test\Unit;
use Magento\Catalog\Model\Product;
use Magento\Customer\Model\Session;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Tweakwise\Magento2Tweakwise\Helper\Cache;
use Tweakwise\Magento2Tweakwise\Model\Analytics\ProductKeyResolver;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2Tweakwise\ViewModel\ProductListItem;

class ProductListItemTest extends Unit
{
    use MockeryPHPUnitIntegration;

    private LayoutInterface&MockInterface $layout;
    private Cache&MockInterface $cacheHelper;
    private Config&MockInterface $config;
    private ProductKeyResolver&MockInterface $productKeyResolver;
    private AbstractBlock&MockInterface $itemRendererBlock;
    private AbstractBlock&MockInterface $parentBlock;
    private ProductListItem $subject;

    protected function _before(): void
    {
        $this->layout = Mockery::mock(LayoutInterface::class);
        $this->cacheHelper = Mockery::mock(Cache::class);
        $this->config = Mockery::mock(Config::class);
        $this->productKeyResolver = Mockery::mock(ProductKeyResolver::class);

        $this->cacheHelper->shouldReceive('personalMerchandisingCanBeApplied')->andReturn(false);
        $this->config->shouldReceive('isGroupedProductsEnabled')->andReturn(false);

        $store = Mockery::mock(StoreInterface::class);
        $store->shouldReceive('getId')->andReturn(1);
        $storeManager = Mockery::mock(StoreManagerInterface::class);
        $storeManager->shouldReceive('getStore')->andReturn($store);

        $this->itemRendererBlock = Mockery::mock(AbstractBlock::class);
        $this->itemRendererBlock->shouldReceive('setData')->andReturnSelf()->byDefault();
        $this->itemRendererBlock->shouldReceive('toHtml')->andReturn('<li>rendered</li>');
        $this->layout->shouldReceive('getBlock')
            ->with('tweakwise.catalog.product.list.item')
            ->andReturn($this->itemRendererBlock);

        $this->parentBlock = Mockery::mock(AbstractBlock::class);
        $this->parentBlock->shouldReceive('getChildBlock')->with('details.renderers')->andReturn(false);
        $this->parentBlock->shouldReceive('getPositioned')->andReturn(null);
        $this->parentBlock->shouldReceive('getData')->with('outputHelper')->andReturn(null);

        $this->subject = new ProductListItem(
            $this->layout,
            $this->cacheHelper,
            $storeManager,
            Mockery::mock(Session::class),
            $this->config,
            $this->productKeyResolver
        );
    }

    public function testGetItemHtmlResolvesTheChildTweakwiseMatchedAgainstTheActiveFilterContext(): void
    {
        $product = Mockery::mock(Product::class);
        $product->shouldReceive('getId')->andReturn(10);
        $product->shouldReceive('getData')->with('tw_id')->andReturn(21);

        $this->productKeyResolver->shouldReceive('resolve')->once()->with('21-10', 1, false)->andReturn('K21-K10');
        $this->itemRendererBlock->shouldReceive('setData')->once()->with('tw_id', 'K21-K10')->andReturnSelf();

        $result = $this->subject->getItemHtml($product, $this->parentBlock, 'grid', 'default', 'category_page_grid', false);

        $this->assertSame('<li>rendered</li>', $result);
    }

    public function testGetItemHtmlResolvesDirectlyFromTheMagentoIdWhenTweakwiseHasNotMatchedAChild(): void
    {
        $product = Mockery::mock(Product::class);
        $product->shouldReceive('getId')->andReturn(42);
        $product->shouldReceive('getData')->with('tw_id')->andReturn(null);

        $this->productKeyResolver->shouldReceive('resolve')->once()->with('42', 1, false)->andReturn('10001042');
        $this->itemRendererBlock->shouldReceive('setData')->once()->with('tw_id', '10001042')->andReturnSelf();

        $result = $this->subject->getItemHtml($product, $this->parentBlock, 'grid', 'default', 'category_page_grid', false);

        $this->assertSame('<li>rendered</li>', $result);
    }
}
