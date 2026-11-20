<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Analytics;

use Emico\CodeCept\Test\Unit;
use Magento\Bundle\Model\Product\Type as Bundle;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tweakwise\Magento2Tweakwise\Model\Analytics\GroupedProductIdResolver;

class GroupedProductIdResolverTest extends Unit
{
    use MockeryPHPUnitIntegration;

    private GroupedProductIdResolver $subject;

    protected function _before(): void
    {
        $this->subject = new GroupedProductIdResolver();
    }

    public function testResolveReturnsRawIdForSimpleProducts(): void
    {
        $product = Mockery::mock(Product::class);
        $product->shouldReceive('getId')->andReturn(42);
        $product->shouldReceive('getTypeId')->once()->andReturn(Type::TYPE_SIMPLE);

        $this->assertSame(42, $this->subject->resolve($product));
    }

    public function testResolveReturnsRawIdWhenThereAreNoAssociatedProducts(): void
    {
        $typeInstance = Mockery::mock(Configurable::class);
        $typeInstance->shouldReceive('getUsedProducts')->once()->andReturn([]);

        $product = Mockery::mock(Product::class);
        $product->shouldReceive('getId')->andReturn(10);
        $product->shouldReceive('getTypeId')->once()->andReturn('configurable');
        $product->shouldReceive('getTypeInstance')->once()->andReturn($typeInstance);

        $this->assertSame(10, $this->subject->resolve($product));
    }

    public function testResolveCombinesFirstAssociatedProductWithParentForConfigurableProducts(): void
    {
        $childProduct = Mockery::mock(Product::class);
        $childProduct->shouldReceive('getId')->andReturn(11);

        $typeInstance = Mockery::mock(Configurable::class);
        $typeInstance->shouldReceive('getUsedProducts')->once()->andReturn([$childProduct]);

        $product = Mockery::mock(Product::class);
        $product->shouldReceive('getId')->andReturn(10);
        $product->shouldReceive('getTypeId')->once()->andReturn('configurable');
        $product->shouldReceive('getTypeInstance')->once()->andReturn($typeInstance);

        $this->assertSame('11-10', $this->subject->resolve($product));
    }

    public function testResolveReturnsRawIdForGroupedProducts(): void
    {
        $typeInstance = Mockery::mock(Grouped::class);
        $typeInstance->shouldNotReceive('getAssociatedProducts');

        $product = Mockery::mock(Product::class);
        $product->shouldReceive('getId')->andReturn(20);
        $product->shouldReceive('getTypeId')->once()->andReturn('grouped');
        $product->shouldReceive('getTypeInstance')->once()->andReturn($typeInstance);

        $this->assertSame(20, $this->subject->resolve($product));
    }

    public function testResolveReturnsRawIdForBundleProducts(): void
    {
        $typeInstance = Mockery::mock(Bundle::class);
        $typeInstance->shouldNotReceive('getSelectionsCollection');

        $product = Mockery::mock(Product::class);
        $product->shouldReceive('getId')->andReturn(30);
        $product->shouldReceive('getTypeId')->once()->andReturn('bundle');
        $product->shouldReceive('getTypeInstance')->once()->andReturn($typeInstance);

        $this->assertSame(30, $this->subject->resolve($product));
    }

    public function testResolveCastsNumericStringIdsFromTheDatabase(): void
    {
        $childProduct = Mockery::mock(Product::class);
        $childProduct->shouldReceive('getId')->andReturn('11');

        $typeInstance = Mockery::mock(Configurable::class);
        $typeInstance->shouldReceive('getUsedProducts')->once()->andReturn([$childProduct]);

        $product = Mockery::mock(Product::class);
        $product->shouldReceive('getId')->andReturn('10');
        $product->shouldReceive('getTypeId')->once()->andReturn('configurable');
        $product->shouldReceive('getTypeInstance')->once()->andReturn($typeInstance);

        $this->assertSame('11-10', $this->subject->resolve($product));
    }

    public function testResolveReturnsRawIdWhenAssociatedProductIsTheParentItself(): void
    {
        $childProduct = Mockery::mock(Product::class);
        $childProduct->shouldReceive('getId')->andReturn('10');

        $typeInstance = Mockery::mock(Configurable::class);
        $typeInstance->shouldReceive('getUsedProducts')->once()->andReturn([$childProduct]);

        $product = Mockery::mock(Product::class);
        $product->shouldReceive('getId')->andReturn('10');
        $product->shouldReceive('getTypeId')->once()->andReturn('configurable');
        $product->shouldReceive('getTypeInstance')->once()->andReturn($typeInstance);

        $this->assertSame(10, $this->subject->resolve($product));
    }

    public function testResolveReturnsRawIdWhenAssociatedProductIdIsZero(): void
    {
        $childProduct = Mockery::mock(Product::class);
        $childProduct->shouldReceive('getId')->andReturn(0);

        $typeInstance = Mockery::mock(Configurable::class);
        $typeInstance->shouldReceive('getUsedProducts')->once()->andReturn([$childProduct]);

        $product = Mockery::mock(Product::class);
        $product->shouldReceive('getId')->andReturn(10);
        $product->shouldReceive('getTypeId')->once()->andReturn('configurable');
        $product->shouldReceive('getTypeInstance')->once()->andReturn($typeInstance);

        $this->assertSame(10, $this->subject->resolve($product));
    }

    public function testResolveReturnsRawIdForUnsupportedProductTypes(): void
    {
        $typeInstance = Mockery::mock();

        $product = Mockery::mock(Product::class);
        $product->shouldReceive('getId')->andReturn(50);
        $product->shouldReceive('getTypeId')->once()->andReturn('virtual');
        $product->shouldReceive('getTypeInstance')->once()->andReturn($typeInstance);

        $this->assertSame(50, $this->subject->resolve($product));
    }
}
