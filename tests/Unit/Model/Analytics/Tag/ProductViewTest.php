<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Analytics\Tag;

use Emico\CodeCept\Test\Unit;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Tweakwise\Magento2Tweakwise\Model\Analytics\ProductKeyResolver;
use Tweakwise\Magento2Tweakwise\Model\Analytics\Tag\ProductView;
use Tweakwise\Magento2Tweakwise\Model\Config;

class ProductViewTest extends Unit
{
    use MockeryPHPUnitIntegration;

    private Config&MockInterface $tweakwiseConfig;
    private RequestInterface&MockInterface $request;
    private ProductRepositoryInterface&MockInterface $productRepository;
    private ProductKeyResolver&MockInterface $productKeyResolver;
    private ProductView $subject;

    protected function _before(): void
    {
        $this->tweakwiseConfig = Mockery::mock(Config::class);
        $this->request = Mockery::mock(RequestInterface::class);
        $this->productRepository = Mockery::mock(ProductRepositoryInterface::class);
        $this->productKeyResolver = Mockery::mock(ProductKeyResolver::class);

        $store = Mockery::mock(StoreInterface::class);
        $store->shouldReceive('getId')->andReturn(1);
        $storeManager = Mockery::mock(StoreManagerInterface::class);
        $storeManager->shouldReceive('getStore')->andReturn($store);

        $this->subject = new ProductView(
            $this->tweakwiseConfig,
            $storeManager,
            $this->request,
            $this->productRepository,
            $this->productKeyResolver
        );
    }

    public function testGetReturnsZeroWhenThereIsNoProductIdOnTheRequest(): void
    {
        $this->request->shouldReceive('getParam')->once()->with('id')->andReturn(null);
        $this->productKeyResolver->shouldNotReceive('resolve');

        $this->assertSame('0', $this->subject->get());
    }

    public function testGetResolvesDirectlyWhenGroupedProductsAreDisabled(): void
    {
        $this->request->shouldReceive('getParam')->with('id')->andReturn('42');
        $this->tweakwiseConfig->shouldReceive('isGroupedProductsEnabled')->once()->andReturn(false);
        $this->productKeyResolver->shouldReceive('resolve')->once()->with('42', 1, false)->andReturn('10001042');

        $this->assertSame('10001042', $this->subject->get());
    }

    public function testGetPassesRawProductIdWhenGroupedProductIsSimple(): void
    {
        $this->request->shouldReceive('getParam')->with('id')->andReturn('42');
        $this->tweakwiseConfig->shouldReceive('isGroupedProductsEnabled')->once()->andReturn(true);

        $product = Mockery::mock(Product::class);
        $product->shouldReceive('getTypeId')->once()->andReturn(Type::TYPE_SIMPLE);
        $this->productRepository->shouldReceive('getById')->once()->with(42)->andReturn($product);

        $this->productKeyResolver->shouldReceive('resolve')->once()->with('42', 1, true)->andReturn('10001042-10001042');

        $this->assertSame('10001042-10001042', $this->subject->get());
    }

    public function testGetCombinesFirstAssociatedProductWithParentForConfigurableProducts(): void
    {
        $this->request->shouldReceive('getParam')->with('id')->andReturn('10');
        $this->tweakwiseConfig->shouldReceive('isGroupedProductsEnabled')->once()->andReturn(true);

        $childProduct = Mockery::mock(Product::class);
        $childProduct->shouldReceive('getId')->andReturn(11);

        $typeInstance = Mockery::mock(Configurable::class);
        $typeInstance->shouldReceive('getUsedProducts')->once()->andReturn([$childProduct]);

        $product = Mockery::mock(Product::class);
        $product->shouldReceive('getTypeId')->once()->andReturn('configurable');
        $product->shouldReceive('getTypeInstance')->once()->andReturn($typeInstance);
        $this->productRepository->shouldReceive('getById')->once()->with(10)->andReturn($product);

        $this->productKeyResolver->shouldReceive('resolve')->once()->with('11-10', 1, true)->andReturn('K11-K10');

        $this->assertSame('K11-K10', $this->subject->get());
    }

    public function testGetFallsBackToRawProductIdWhenProductCannotBeFound(): void
    {
        $this->request->shouldReceive('getParam')->with('id')->andReturn('99');
        $this->tweakwiseConfig->shouldReceive('isGroupedProductsEnabled')->once()->andReturn(true);
        $this->productRepository->shouldReceive('getById')->once()->with(99)
            ->andThrow(new NoSuchEntityException(__('not found')));

        $this->productKeyResolver->shouldReceive('resolve')->once()->with('99', 1, true)->andReturn('10000199');

        $this->assertSame('10000199', $this->subject->get());
    }

    public function testGetPriceReturnsZeroWhenThereIsNoProductIdOnTheRequest(): void
    {
        $this->request->shouldReceive('getParam')->once()->with('id')->andReturn(null);

        $this->assertSame(0.0, $this->subject->getPrice());
    }

    public function testGetPriceReturnsZeroWhenProductCannotBeFound(): void
    {
        $this->request->shouldReceive('getParam')->with('id')->andReturn('99');
        $this->productRepository->shouldReceive('getById')->once()->with(99)
            ->andThrow(new NoSuchEntityException(__('not found')));

        $this->assertSame(0.0, $this->subject->getPrice());
    }

    public function testGetPriceReturnsTheProductsFinalPrice(): void
    {
        $this->request->shouldReceive('getParam')->with('id')->andReturn('42');
        $product = Mockery::mock(Product::class);
        $product->shouldReceive('getFinalPrice')->once()->andReturn('59.99');
        $this->productRepository->shouldReceive('getById')->once()->with(42)->andReturn($product);

        $this->assertSame(59.99, $this->subject->getPrice());
    }
}
