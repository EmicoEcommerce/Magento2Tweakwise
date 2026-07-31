<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Observer\Event;

use Emico\CodeCept\Test\Unit;
use Magento\Catalog\Api\Data\ProductExtension;
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
use Tweakwise\Magento2Tweakwise\Model\PersonalMerchandisingConfig;
use Tweakwise\Magento2Tweakwise\Observer\Event\StashAddToWishlistEvent;
use Tweakwise\Magento2TweakwiseExport\Model\Helper;

class StashAddToWishlistEventTest extends Unit
{
    use MockeryPHPUnitIntegration;

    private PersonalMerchandisingConfig&MockInterface $config;
    private LoggerInterface&MockInterface $logger;
    private Helper&MockInterface $helper;
    private CustomerSessionDataProvider&MockInterface $customerSessionDataProvider;
    private StashAddToWishlistEvent $subject;

    protected function _before(): void
    {
        $this->config = Mockery::mock(PersonalMerchandisingConfig::class);
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->helper = Mockery::mock(Helper::class);
        $this->customerSessionDataProvider = Mockery::mock(CustomerSessionDataProvider::class);

        $store = Mockery::mock(StoreInterface::class);
        $store->shouldReceive('getId')->andReturn(1);
        $storeManager = Mockery::mock(StoreManagerInterface::class);
        $storeManager->shouldReceive('getStore')->andReturn($store);

        $this->subject = new StashAddToWishlistEvent(
            $this->config,
            $this->logger,
            $this->helper,
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

        $product = Mockery::mock(Product::class);
        $product->shouldReceive('getId')->andReturn(42);

        $this->helper->shouldReceive('getTweakwiseId')->once()->with(1, 42, null)->andReturn('10001042');

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

        $extensionAttributes = Mockery::mock(ProductExtension::class);
        $extensionAttributes->shouldReceive('getConfigurableProductLinks')->andReturn([11, 12]);

        $product = Mockery::mock(Product::class);
        $product->shouldReceive('getId')->andReturn(10);
        $product->shouldReceive('getExtensionAttributes')->andReturn($extensionAttributes);

        $this->helper->shouldReceive('getTweakwiseId')->once()->with(1, 10)->andReturn('55');
        $this->helper->shouldReceive('getTweakwiseId')->once()->with(1, 11, 55)->andReturn('K11-55');

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
