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
use Tweakwise\Magento2Tweakwise\Model\Analytics\PurchaseEventDataResolver;
use Tweakwise\Magento2Tweakwise\Model\PersonalMerchandisingConfig;
use Tweakwise\Magento2TweakwiseExport\Model\Helper;

class PurchaseEventDataResolverTest extends Unit
{
    use MockeryPHPUnitIntegration;

    private Helper&MockInterface $helper;
    private PersonalMerchandisingConfig&MockInterface $config;
    private PurchaseEventDataResolver $subject;

    protected function _before(): void
    {
        $this->helper = Mockery::mock(Helper::class);
        $this->config = Mockery::mock(PersonalMerchandisingConfig::class);

        $store = Mockery::mock(StoreInterface::class);
        $store->shouldReceive('getId')->andReturn(1);

        $storeManager = Mockery::mock(StoreManagerInterface::class);
        $storeManager->shouldReceive('getStore')->andReturn($store);

        $this->subject = new PurchaseEventDataResolver($this->helper, $storeManager, $this->config);
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

        $this->helper->shouldReceive('getTweakwiseId')->once()->with(1, 10)->andReturn('10001010');

        $result = $this->subject->resolve($order);

        $this->assertSame(123.45, $result['revenue']);
    }

    public function testResolveSimpleModeExcludesChildItemsAndKeepsTopLevelItems(): void
    {
        $this->config->shouldReceive('isGroupedProductsEnabled')->andReturn(false);

        $parent = $this->mockItem(null, 100, 10);
        $child = $this->mockItem($parent, 101, 20);
        $order = $this->mockOrder([$parent, $child], '50.00');

        $this->helper->shouldReceive('getTweakwiseId')->once()->with(1, 10)->andReturn('10001010');

        $result = $this->subject->resolve($order);

        $this->assertSame(['10001010'], $result['productKeys']);
    }

    public function testResolveGroupedModeReportsChildParentPairForSingleChild(): void
    {
        $this->config->shouldReceive('isGroupedProductsEnabled')->andReturn(true);

        $parent = $this->mockItem(null, 100, 1);
        $child = $this->mockItem($parent, 101, 5);
        $order = $this->mockOrder([$parent, $child], '59.00');

        $this->helper->shouldReceive('getTweakwiseId')->once()->with(1, 1)->andReturn('55');
        $this->helper->shouldReceive('getTweakwiseId')->once()->with(1, 5, 55)->andReturn('K5-55');

        $result = $this->subject->resolve($order);

        $this->assertSame(['K5-55'], $result['productKeys']);
    }

    public function testResolveGroupedModeReportsPlainItemBoughtDirectlyAsItsOwnProduct(): void
    {
        $this->config->shouldReceive('isGroupedProductsEnabled')->andReturn(true);

        $plainItem = $this->mockItem(null, 200, 7);
        $order = $this->mockOrder([$plainItem], '19.99');

        $this->helper->shouldReceive('getTweakwiseId')->once()->with(1, 7)->andReturn('K7');

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

        $this->helper->shouldReceive('getTweakwiseId')->once()->with(1, 3)->andReturn('K3');

        $result = $this->subject->resolve($order);

        $this->assertSame(['K3'], $result['productKeys']);
    }
}
