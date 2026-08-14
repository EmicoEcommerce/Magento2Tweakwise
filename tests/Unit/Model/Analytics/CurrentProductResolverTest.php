<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Analytics;

use Emico\CodeCept\Test\Unit;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Tweakwise\Magento2Tweakwise\Model\Analytics\CurrentProductResolver;

class CurrentProductResolverTest extends Unit
{
    use MockeryPHPUnitIntegration;

    private RequestInterface&MockInterface $request;
    private ProductRepositoryInterface&MockInterface $productRepository;
    private CurrentProductResolver $subject;

    protected function _before(): void
    {
        $this->request = Mockery::mock(RequestInterface::class);
        $this->productRepository = Mockery::mock(ProductRepositoryInterface::class);

        $store = Mockery::mock(StoreInterface::class);
        $store->shouldReceive('getId')->andReturn(1);
        $storeManager = Mockery::mock(StoreManagerInterface::class);
        $storeManager->shouldReceive('getStore')->andReturn($store);

        $this->subject = new CurrentProductResolver(
            $this->request,
            $this->productRepository,
            $storeManager
        );
    }

    public function testGetProductIdReadsIdParam(): void
    {
        $this->request->shouldReceive('getParam')->once()->with('id')->andReturn('42');
        $this->request->shouldReceive('getActionName')->once()->andReturn('view');

        $this->assertSame(42, $this->subject->getProductId());
    }

    public function testGetProductIdFallsBackToProductIdParamOnConfigureAction(): void
    {
        $this->request->shouldReceive('getParam')->once()->with('id')->andReturn('42');
        $this->request->shouldReceive('getActionName')->once()->andReturn('configure');
        $this->request->shouldReceive('getParam')->once()->with('product_id')->andReturn('99');

        $this->assertSame(99, $this->subject->getProductId());
    }

    public function testGetProductIdFallsBackToProductIdParamWhenIdIsMissing(): void
    {
        $this->request->shouldReceive('getParam')->once()->with('id')->andReturn(null);
        $this->request->shouldReceive('getActionName')->once()->andReturn('view');
        $this->request->shouldReceive('getParam')->once()->with('product_id')->andReturn('99');

        $this->assertSame(99, $this->subject->getProductId());
    }

    public function testGetProductIdMemoizesTheResolvedId(): void
    {
        $this->request->shouldReceive('getParam')->once()->with('id')->andReturn('42');
        $this->request->shouldReceive('getActionName')->once()->andReturn('view');

        $this->assertSame(42, $this->subject->getProductId());
        $this->assertSame(42, $this->subject->getProductId());
    }

    public function testGetProductReturnsNullWhenThereIsNoProductId(): void
    {
        $this->request->shouldReceive('getParam')->once()->with('id')->andReturn(null);
        $this->request->shouldReceive('getActionName')->once()->andReturn('view');
        $this->request->shouldReceive('getParam')->once()->with('product_id')->andReturn(null);
        $this->productRepository->shouldNotReceive('getById');

        $this->assertNull($this->subject->getProduct());
    }

    public function testGetProductReturnsNullWhenProductCannotBeFound(): void
    {
        $this->request->shouldReceive('getParam')->once()->with('id')->andReturn('99');
        $this->request->shouldReceive('getActionName')->once()->andReturn('view');
        $this->productRepository->shouldReceive('getById')->once()->with(99, false, 1)
            ->andThrow(new NoSuchEntityException(__('not found')));

        $this->assertNull($this->subject->getProduct());
    }

    public function testGetProductFetchesTheProductForTheCurrentStore(): void
    {
        $this->request->shouldReceive('getParam')->once()->with('id')->andReturn('42');
        $this->request->shouldReceive('getActionName')->once()->andReturn('view');
        $product = Mockery::mock(Product::class);
        $this->productRepository->shouldReceive('getById')->once()->with(42, false, 1)->andReturn($product);

        $this->assertSame($product, $this->subject->getProduct());
    }

    public function testGetProductOnlyFetchesTheProductOnce(): void
    {
        $this->request->shouldReceive('getParam')->once()->with('id')->andReturn('42');
        $this->request->shouldReceive('getActionName')->once()->andReturn('view');
        $product = Mockery::mock(Product::class);
        $this->productRepository->shouldReceive('getById')->once()->with(42, false, 1)->andReturn($product);

        $this->subject->getProduct();
        $this->subject->getProduct();
    }
}
