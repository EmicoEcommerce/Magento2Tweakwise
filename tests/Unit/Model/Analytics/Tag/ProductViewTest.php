<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Analytics\Tag;

use Emico\CodeCept\Test\Unit;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Tweakwise\Magento2Tweakwise\Model\Analytics\CurrentProductResolver;
use Tweakwise\Magento2Tweakwise\Model\Analytics\GroupedProductIdResolver;
use Tweakwise\Magento2Tweakwise\Model\Analytics\ProductKeyResolver;
use Tweakwise\Magento2Tweakwise\Model\Analytics\Tag\ProductView;
use Tweakwise\Magento2Tweakwise\Model\Config;

class ProductViewTest extends Unit
{
    use MockeryPHPUnitIntegration;

    private Config&MockInterface $tweakwiseConfig;
    private CurrentProductResolver&MockInterface $currentProductResolver;
    private ProductKeyResolver&MockInterface $productKeyResolver;
    private GroupedProductIdResolver&MockInterface $groupedProductIdResolver;
    private ProductView $subject;

    protected function _before(): void
    {
        $this->tweakwiseConfig = Mockery::mock(Config::class);
        $this->currentProductResolver = Mockery::mock(CurrentProductResolver::class);
        $this->productKeyResolver = Mockery::mock(ProductKeyResolver::class);
        $this->groupedProductIdResolver = Mockery::mock(GroupedProductIdResolver::class);

        $store = Mockery::mock(StoreInterface::class);
        $store->shouldReceive('getId')->andReturn(1);
        $storeManager = Mockery::mock(StoreManagerInterface::class);
        $storeManager->shouldReceive('getStore')->andReturn($store);

        $this->subject = new ProductView(
            $this->tweakwiseConfig,
            $storeManager,
            $this->currentProductResolver,
            $this->productKeyResolver,
            $this->groupedProductIdResolver
        );
    }

    public function testGetReturnsZeroWhenThereIsNoProductIdOnTheRequest(): void
    {
        $this->currentProductResolver->shouldReceive('getProductId')->once()->andReturn(0);
        $this->productKeyResolver->shouldNotReceive('resolve');

        $this->assertSame('0', $this->subject->get());
    }

    public function testGetResolvesDirectlyWhenGroupedProductsAreDisabled(): void
    {
        $this->currentProductResolver->shouldReceive('getProductId')->once()->andReturn(42);
        $this->tweakwiseConfig->shouldReceive('isGroupedProductsEnabled')->once()->andReturn(false);
        $this->productKeyResolver->shouldReceive('resolve')->once()->with('42', 1, false)->andReturn('10001042');

        $this->assertSame('10001042', $this->subject->get());
    }

    public function testGetDelegatesToGroupedProductIdResolverWhenGroupedProductsAreEnabled(): void
    {
        $this->currentProductResolver->shouldReceive('getProductId')->once()->andReturn(10);
        $this->tweakwiseConfig->shouldReceive('isGroupedProductsEnabled')->once()->andReturn(true);

        $product = Mockery::mock(Product::class);
        $this->currentProductResolver->shouldReceive('getProduct')->once()->andReturn($product);
        $this->groupedProductIdResolver->shouldReceive('resolve')->once()->with($product)->andReturn('11-10');

        $this->productKeyResolver->shouldReceive('resolve')->once()->with('11-10', 1, true)->andReturn('K11-K10');

        $this->assertSame('K11-K10', $this->subject->get());
    }

    public function testGetFallsBackToRawProductIdWhenProductCannotBeFound(): void
    {
        $this->currentProductResolver->shouldReceive('getProductId')->once()->andReturn(99);
        $this->tweakwiseConfig->shouldReceive('isGroupedProductsEnabled')->once()->andReturn(true);
        $this->currentProductResolver->shouldReceive('getProduct')->once()->andReturn(null);
        $this->groupedProductIdResolver->shouldNotReceive('resolve');

        $this->productKeyResolver->shouldReceive('resolve')->once()->with('99', 1, true)->andReturn('10000199');

        $this->assertSame('10000199', $this->subject->get());
    }

    public function testGetPriceReturnsZeroWhenProductCannotBeFound(): void
    {
        $this->currentProductResolver->shouldReceive('getProduct')->once()->andReturn(null);

        $this->assertSame(0.0, $this->subject->getPrice());
    }

    public function testGetPriceReturnsTheProductsFinalPrice(): void
    {
        $product = Mockery::mock(Product::class);
        $product->shouldReceive('getFinalPrice')->once()->andReturn('59.99');
        $this->currentProductResolver->shouldReceive('getProduct')->once()->andReturn($product);

        $this->assertSame(59.99, $this->subject->getPrice());
    }

    public function testIsAmbiguousIsFalseWhenGroupedProductsAreDisabled(): void
    {
        $this->tweakwiseConfig->shouldReceive('isGroupedProductsEnabled')->once()->andReturn(false);
        $this->currentProductResolver->shouldNotReceive('getProduct');

        $this->assertFalse($this->subject->isAmbiguous());
    }

    public function testIsAmbiguousIsFalseWhenProductCannotBeFound(): void
    {
        $this->tweakwiseConfig->shouldReceive('isGroupedProductsEnabled')->once()->andReturn(true);
        $this->currentProductResolver->shouldReceive('getProduct')->once()->andReturn(null);

        $this->assertFalse($this->subject->isAmbiguous());
    }

    public function testIsAmbiguousIsFalseForASimpleProduct(): void
    {
        $this->tweakwiseConfig->shouldReceive('isGroupedProductsEnabled')->once()->andReturn(true);

        $product = Mockery::mock(Product::class);
        $product->shouldReceive('getTypeId')->andReturn(Type::TYPE_SIMPLE);
        $this->currentProductResolver->shouldReceive('getProduct')->once()->andReturn($product);

        $this->assertFalse($this->subject->isAmbiguous());
    }

    public function testIsAmbiguousIsTrueForAConfigurableProductWithNoVariantSelectedYet(): void
    {
        $this->tweakwiseConfig->shouldReceive('isGroupedProductsEnabled')->once()->andReturn(true);

        $product = Mockery::mock(Product::class);
        $product->shouldReceive('getTypeId')->andReturn('configurable');
        $this->currentProductResolver->shouldReceive('getProduct')->once()->andReturn($product);

        $this->assertTrue($this->subject->isAmbiguous());
    }
}
