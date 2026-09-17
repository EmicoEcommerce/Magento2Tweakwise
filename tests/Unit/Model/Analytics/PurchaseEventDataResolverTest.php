<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Analytics;

use Emico\CodeCept\Test\Unit;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Tweakwise\Magento2Tweakwise\Model\Analytics\ProductKeyResolver;
use Tweakwise\Magento2Tweakwise\Model\Analytics\PurchaseEventDataResolver;
use Tweakwise\Magento2Tweakwise\Model\PersonalMerchandisingConfig;

class PurchaseEventDataResolverTest extends Unit
{
    use MockeryPHPUnitIntegration;

    private ProductKeyResolver&MockInterface $productKeyResolver;
    private PersonalMerchandisingConfig&MockInterface $config;
    private PurchaseEventDataResolver $subject;

    protected function _before(): void
    {
        $this->productKeyResolver = Mockery::mock(ProductKeyResolver::class);
        $this->config = Mockery::mock(PersonalMerchandisingConfig::class);

        $store = Mockery::mock(StoreInterface::class);
        $store->shouldReceive('getId')->andReturn(1);

        $storeManager = Mockery::mock(StoreManagerInterface::class);
        $storeManager->shouldReceive('getStore')->andReturn($store);

        $this->subject = new PurchaseEventDataResolver($this->productKeyResolver, $storeManager, $this->config);
    }

    private function mockItem(?Item $parent, int $id, int $productId): Item&MockInterface
    {
        $item = Mockery::mock(Item::class);
        $item->shouldReceive('getParentItem')->andReturn($parent);
        $item->shouldReceive('getId')->andReturn($id);
        $item->shouldReceive('getProductId')->andReturn($productId);

        return $item;
    }

    private function mockOrder(array $items, string $baseSubtotal): Order&MockInterface
    {
        $order = Mockery::mock(Order::class);
        $order->shouldReceive('getAllItems')->once()->andReturn($items);
        $order->shouldReceive('getBaseSubtotal')->once()->andReturn($baseSubtotal);

        return $order;
    }

    public function testResolveReturnsRevenueAsFloatFromBaseSubtotal(): void
    {
        $this->config->shouldReceive('isGroupedProductsEnabled')->andReturn(false);
        $item = $this->mockItem(null, 1, 10);
        $order = $this->mockOrder([$item], '123.45');

        $this->productKeyResolver->shouldReceive('resolve')->once()->with('10', 1, false)->andReturn('10001010');

        $result = $this->subject->resolve($order);

        $this->assertSame(123.45, $result['revenue']);
    }

    public function testResolveSimpleModeExcludesChildItemsAndKeepsTopLevelItems(): void
    {
        $this->config->shouldReceive('isGroupedProductsEnabled')->andReturn(false);

        $parent = $this->mockItem(null, 100, 10);
        $child = $this->mockItem($parent, 101, 20);
        $order = $this->mockOrder([$parent, $child], '50.00');

        $this->productKeyResolver->shouldReceive('resolve')->once()->with('10', 1, false)->andReturn('10001010');

        $result = $this->subject->resolve($order);

        $this->assertSame(['10001010'], $result['productKeys']);
    }

    public function testResolveGroupedModeReportsChildParentPairForSingleChild(): void
    {
        $this->config->shouldReceive('isGroupedProductsEnabled')->andReturn(true);

        $parent = $this->mockItem(null, 100, 1);
        $child = $this->mockItem($parent, 101, 5);
        $order = $this->mockOrder([$parent, $child], '59.00');

        $this->productKeyResolver->shouldReceive('resolve')->once()->with('5-1', 1, true)->andReturn('K5-55');

        $result = $this->subject->resolve($order);

        $this->assertSame(['K5-55'], $result['productKeys']);
    }

    public function testResolveGroupedModeReportsPlainItemBoughtDirectlyAsItsOwnProduct(): void
    {
        $this->config->shouldReceive('isGroupedProductsEnabled')->andReturn(true);

        $plainItem = $this->mockItem(null, 200, 7);
        $order = $this->mockOrder([$plainItem], '19.99');

        $this->productKeyResolver->shouldReceive('resolve')->once()->with('7', 1, false)->andReturn('K7');

        $result = $this->subject->resolve($order);

        $this->assertSame(['K7'], $result['productKeys']);
    }

    public function testResolveGroupedModeReportsBundleWithMultipleChildrenAsParentOnly(): void
    {
        $this->config->shouldReceive('isGroupedProductsEnabled')->andReturn(true);

        $parent = $this->mockItem(null, 300, 3);
        $childOne = $this->mockItem($parent, 301, 11);
        $childTwo = $this->mockItem($parent, 302, 12);
        $order = $this->mockOrder([$parent, $childOne, $childTwo], '99.00');

        $this->productKeyResolver->shouldReceive('resolve')->once()->with('3', 1, false)->andReturn('K3');

        $result = $this->subject->resolve($order);

        $this->assertSame(['K3'], $result['productKeys']);
    }
}
