<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Analytics\Tag;

use Emico\CodeCept\Test\Unit;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Tweakwise\Magento2Tweakwise\Model\Analytics\CurrentProductResolver;
use Tweakwise\Magento2Tweakwise\Model\Analytics\ProductKeyResolver;
use Tweakwise\Magento2Tweakwise\Model\Analytics\Tag\ProductView;
use Tweakwise\Magento2Tweakwise\Model\Config;

class ProductViewTest extends Unit
{
    use MockeryPHPUnitIntegration;

    private Config&MockInterface $tweakwiseConfig;
    private CurrentProductResolver&MockInterface $currentProductResolver;
    private ProductKeyResolver&MockInterface $productKeyResolver;
    private ProductView $subject;

    protected function _before(): void
    {
        $this->tweakwiseConfig = Mockery::mock(Config::class);
        $this->currentProductResolver = Mockery::mock(CurrentProductResolver::class);
        $this->productKeyResolver = Mockery::mock(ProductKeyResolver::class);

        $store = Mockery::mock(StoreInterface::class);
        $store->shouldReceive('getId')->andReturn(1);
        $storeManager = Mockery::mock(StoreManagerInterface::class);
        $storeManager->shouldReceive('getStore')->andReturn($store);

        $this->subject = new ProductView(
            $this->tweakwiseConfig,
            $storeManager,
            $this->currentProductResolver,
            $this->productKeyResolver
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

    public function testGetPassesRawProductIdWhenGroupedProductIsSimple(): void
    {
        $this->currentProductResolver->shouldReceive('getProductId')->once()->andReturn(42);
        $this->tweakwiseConfig->shouldReceive('isGroupedProductsEnabled')->once()->andReturn(true);

        $product = Mockery::mock(Product::class);
        $product->shouldReceive('getTypeId')->once()->andReturn(Type::TYPE_SIMPLE);
        $this->currentProductResolver->shouldReceive('getProduct')->once()->andReturn($product);

        $this->productKeyResolver->shouldReceive('resolve')->once()->with('42', 1, true)->andReturn('10001042-10001042');

        $this->assertSame('10001042-10001042', $this->subject->get());
    }

    public function testGetCombinesFirstAssociatedProductWithParentForConfigurableProducts(): void
    {
        $this->currentProductResolver->shouldReceive('getProductId')->once()->andReturn(10);
        $this->tweakwiseConfig->shouldReceive('isGroupedProductsEnabled')->once()->andReturn(true);

        $childProduct = Mockery::mock(Product::class);
        $childProduct->shouldReceive('getId')->andReturn(11);

        $typeInstance = Mockery::mock(Configurable::class);
        $typeInstance->shouldReceive('getUsedProducts')->once()->andReturn([$childProduct]);

        $product = Mockery::mock(Product::class);
        $product->shouldReceive('getTypeId')->once()->andReturn('configurable');
        $product->shouldReceive('getTypeInstance')->once()->andReturn($typeInstance);
        $this->currentProductResolver->shouldReceive('getProduct')->once()->andReturn($product);

        $this->productKeyResolver->shouldReceive('resolve')->once()->with('11-10', 1, true)->andReturn('K11-K10');

        $this->assertSame('K11-K10', $this->subject->get());
    }

    public function testGetFallsBackToRawProductIdWhenProductCannotBeFound(): void
    {
        $this->currentProductResolver->shouldReceive('getProductId')->once()->andReturn(99);
        $this->tweakwiseConfig->shouldReceive('isGroupedProductsEnabled')->once()->andReturn(true);
        $this->currentProductResolver->shouldReceive('getProduct')->once()->andReturn(null);

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
}
