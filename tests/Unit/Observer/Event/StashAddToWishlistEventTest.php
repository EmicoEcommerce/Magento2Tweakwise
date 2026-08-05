<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Observer\Event;

use Emico\CodeCept\Test\Unit;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Tweakwise\Magento2Tweakwise\Model\Analytics\CustomerSessionDataProvider;
use Tweakwise\Magento2Tweakwise\Model\Analytics\GroupedProductIdResolver;
use Tweakwise\Magento2Tweakwise\Model\Analytics\ProductKeyResolver;
use Tweakwise\Magento2Tweakwise\Model\PersonalMerchandisingConfig;
use Tweakwise\Magento2Tweakwise\Observer\Event\StashAddToWishlistEvent;

class StashAddToWishlistEventTest extends Unit
{
    use MockeryPHPUnitIntegration;

    private PersonalMerchandisingConfig&MockInterface $config;
    private LoggerInterface&MockInterface $logger;
    private ProductKeyResolver&MockInterface $productKeyResolver;
    private GroupedProductIdResolver&MockInterface $groupedProductIdResolver;
    private CustomerSessionDataProvider&MockInterface $customerSessionDataProvider;
    private StashAddToWishlistEvent $subject;

    protected function _before(): void
    {
        $this->config = Mockery::mock(PersonalMerchandisingConfig::class);
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->productKeyResolver = Mockery::mock(ProductKeyResolver::class);
        $this->groupedProductIdResolver = Mockery::mock(GroupedProductIdResolver::class);
        $this->customerSessionDataProvider = Mockery::mock(CustomerSessionDataProvider::class);

        $store = Mockery::mock(StoreInterface::class);
        $store->shouldReceive('getId')->andReturn(1);
        $storeManager = Mockery::mock(StoreManagerInterface::class);
        $storeManager->shouldReceive('getStore')->andReturn($store);

        $this->subject = new StashAddToWishlistEvent(
            $this->config,
            $this->logger,
            $this->productKeyResolver,
            $this->groupedProductIdResolver,
            $storeManager,
            $this->customerSessionDataProvider
        );
    }

    private function observerWith(ProductInterface $product): Observer
    {
        $event = Mockery::mock(Event::class);
        $event->shouldReceive('getProduct')->andReturn($product);

        $observer = Mockery::mock(Observer::class);
        $observer->shouldReceive('getEvent')->andReturn($event);

        return $observer;
    }

    public function testExecuteDoesNothingWhenAnalyticsIsDisabled(): void
    {
        $this->config->shouldReceive('isAnalyticsEnabled')->once()->andReturn(false);
        $this->customerSessionDataProvider->shouldNotReceive('add');

        $observer = Mockery::mock(Observer::class);
        $observer->shouldNotReceive('getEvent');

        $this->subject->execute($observer);
    }

    public function testExecuteIgnoresNonConcreteProductInstances(): void
    {
        $this->config->shouldReceive('isAnalyticsEnabled')->once()->andReturn(true);
        $this->customerSessionDataProvider->shouldNotReceive('add');

        $product = Mockery::mock(ProductInterface::class);
        $observer = $this->observerWith($product);

        $this->subject->execute($observer);
    }

    public function testExecuteStashesASimpleProductWishlistAdd(): void
    {
        $this->config->shouldReceive('isAnalyticsEnabled')->once()->andReturn(true);
        $this->config->shouldReceive('isGroupedProductsEnabled')->once()->andReturn(false);
        $this->groupedProductIdResolver->shouldNotReceive('resolve');

        $product = Mockery::mock(Product::class);
        $product->shouldReceive('getId')->andReturn(42);

        $this->productKeyResolver->shouldReceive('resolve')->once()->with('42', 1, false)->andReturn('10001042');

        $this->customerSessionDataProvider->shouldReceive('add')->once()->with('addtowishlist_event', [
            'productKey' => '10001042',
        ]);

        $observer = $this->observerWith($product);
        $this->subject->execute($observer);
    }

    public function testExecuteUsesTheFirstConfigurableChildProductId(): void
    {
        $this->config->shouldReceive('isAnalyticsEnabled')->once()->andReturn(true);
        $this->config->shouldReceive('isGroupedProductsEnabled')->once()->andReturn(true);

        $product = Mockery::mock(Product::class);
        $product->shouldReceive('getId')->andReturn(10);

        $this->groupedProductIdResolver->shouldReceive('resolve')->once()->with($product)->andReturn('11-10');
        $this->productKeyResolver->shouldReceive('resolve')->once()->with('11-10', 1, true)->andReturn('K11-55');

        $this->customerSessionDataProvider->shouldReceive('add')->once()->with('addtowishlist_event', [
            'productKey' => 'K11-55',
        ]);

        $observer = $this->observerWith($product);
        $this->subject->execute($observer);
    }

    public function testExecuteSwallowsExceptionsAndLogsThem(): void
    {
        $this->config->shouldReceive('isAnalyticsEnabled')->once()->andThrow(new RuntimeException('boom'));
        $this->logger->shouldReceive('error')->once()->with(
            'Tweakwise add to wishlist event could not be stashed',
            ['message' => 'boom']
        );

        $observer = Mockery::mock(Observer::class);
        $observer->shouldNotReceive('getEvent');

        $this->subject->execute($observer);
    }
}
