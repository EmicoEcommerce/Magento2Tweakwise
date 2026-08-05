<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Observer\Event;

use Emico\CodeCept\Test\Unit;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Tweakwise\Magento2Tweakwise\Model\Analytics\CheckoutSessionDataProvider;
use Tweakwise\Magento2Tweakwise\Model\Analytics\ProductKeyResolver;
use Tweakwise\Magento2Tweakwise\Model\PersonalMerchandisingConfig;
use Tweakwise\Magento2Tweakwise\Observer\Event\StashAddToCartEvent;

class StashAddToCartEventTest extends Unit
{
    use MockeryPHPUnitIntegration;

    private PersonalMerchandisingConfig&MockInterface $config;
    private LoggerInterface&MockInterface $logger;
    private ProductKeyResolver&MockInterface $productKeyResolver;
    private CheckoutSessionDataProvider&MockInterface $checkoutSessionDataProvider;
    private StashAddToCartEvent $subject;

    protected function _before(): void
    {
        $this->config = Mockery::mock(PersonalMerchandisingConfig::class);
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->productKeyResolver = Mockery::mock(ProductKeyResolver::class);
        $this->checkoutSessionDataProvider = Mockery::mock(CheckoutSessionDataProvider::class);

        $store = Mockery::mock(StoreInterface::class);
        $store->shouldReceive('getId')->andReturn(1);
        $storeManager = Mockery::mock(StoreManagerInterface::class);
        $storeManager->shouldReceive('getStore')->andReturn($store);

        $this->subject = new StashAddToCartEvent(
            $this->config,
            $this->logger,
            $this->productKeyResolver,
            $storeManager,
            $this->checkoutSessionDataProvider
        );
    }

    private function observerWith(ProductInterface $product, CartItemInterface $quoteItem): Observer
    {
        $event = Mockery::mock(Event::class);
        $event->shouldReceive('getProduct')->andReturn($product);
        $event->shouldReceive('getQuoteItem')->andReturn($quoteItem);

        $observer = Mockery::mock(Observer::class);
        $observer->shouldReceive('getEvent')->andReturn($event);

        return $observer;
    }

    public function testExecuteDoesNothingWhenAnalyticsIsDisabled(): void
    {
        $this->config->shouldReceive('isAnalyticsEnabled')->once()->andReturn(false);
        $this->checkoutSessionDataProvider->shouldNotReceive('add');

        $observer = Mockery::mock(Observer::class);
        $observer->shouldNotReceive('getEvent');

        $this->subject->execute($observer);
    }

    public function testExecuteIgnoresNonConcreteProductOrQuoteItemInstances(): void
    {
        $this->config->shouldReceive('isAnalyticsEnabled')->once()->andReturn(true);
        $this->checkoutSessionDataProvider->shouldNotReceive('add');

        $product = Mockery::mock(ProductInterface::class);
        $quoteItem = Mockery::mock(CartItemInterface::class);
        $observer = $this->observerWith($product, $quoteItem);

        $this->subject->execute($observer);
    }

    public function testExecuteStashesASimpleProductAddToCart(): void
    {
        $this->config->shouldReceive('isAnalyticsEnabled')->once()->andReturn(true);
        $this->config->shouldReceive('isGroupedProductsEnabled')->once()->andReturn(false);

        $priceModel = Mockery::mock();
        $priceModel->shouldReceive('getFinalPrice')->with(2.0, Mockery::type(Product::class))->andReturn(10.0);

        $product = Mockery::mock(Product::class);
        $product->shouldReceive('getPriceModel')->andReturn($priceModel);

        $quoteItem = Mockery::mock(Item::class);
        $quoteItem->shouldReceive('getQtyToAdd')->andReturn(2.0);
        $quoteItem->shouldReceive('getProductId')->andReturn(42);

        $this->productKeyResolver->shouldReceive('resolve')->once()->with('42', 1, false)->andReturn('10001042');

        $this->checkoutSessionDataProvider->shouldReceive('add')->once()->with('addtocart_event', [
            'productKey' => '10001042',
            'quantity' => 2.0,
            'totalAmount' => 20.0,
        ]);

        $observer = $this->observerWith($product, $quoteItem);
        $this->subject->execute($observer);
    }

    public function testExecuteUsesTheSelectedChildProductIdForConfigurableProducts(): void
    {
        $this->config->shouldReceive('isAnalyticsEnabled')->once()->andReturn(true);
        $this->config->shouldReceive('isGroupedProductsEnabled')->once()->andReturn(true);

        $priceModel = Mockery::mock();
        $priceModel->shouldReceive('getFinalPrice')->andReturn(15.0);

        $product = Mockery::mock(Product::class);
        $product->shouldReceive('getPriceModel')->andReturn($priceModel);
        $product->shouldReceive('getId')->andReturn(10);

        $quoteItem = Mockery::mock(Item::class);
        $quoteItem->shouldReceive('getQtyToAdd')->andReturn(1.0);
        $quoteItem->shouldReceive('getProductId')->andReturn(10);
        $quoteItem->shouldReceive('getQtyOptions')->andReturn([11 => Mockery::mock()]);

        $this->productKeyResolver->shouldReceive('resolve')->once()->with('11-10', 1, true)->andReturn('K11-55');

        $this->checkoutSessionDataProvider->shouldReceive('add')->once()->with('addtocart_event', [
            'productKey' => 'K11-55',
            'quantity' => 1.0,
            'totalAmount' => 15.0,
        ]);

        $observer = $this->observerWith($product, $quoteItem);
        $this->subject->execute($observer);
    }

    public function testExecuteTreatsProductAsItsOwnGroupWhenGroupedProductsAreEnabledButThereAreNoQtyOptions(): void
    {
        $this->config->shouldReceive('isAnalyticsEnabled')->once()->andReturn(true);
        $this->config->shouldReceive('isGroupedProductsEnabled')->once()->andReturn(true);

        $priceModel = Mockery::mock();
        $priceModel->shouldReceive('getFinalPrice')->andReturn(5.0);

        $product = Mockery::mock(Product::class);
        $product->shouldReceive('getPriceModel')->andReturn($priceModel);
        $product->shouldReceive('getId')->andReturn(42);

        $quoteItem = Mockery::mock(Item::class);
        $quoteItem->shouldReceive('getQtyToAdd')->andReturn(1.0);
        $quoteItem->shouldReceive('getProductId')->andReturn(42);
        $quoteItem->shouldReceive('getQtyOptions')->andReturn([]);

        $this->productKeyResolver->shouldReceive('resolve')->once()->with('42-42', 1, true)->andReturn('10001042-10001042');

        $this->checkoutSessionDataProvider->shouldReceive('add')->once()->with('addtocart_event', [
            'productKey' => '10001042-10001042',
            'quantity' => 1.0,
            'totalAmount' => 5.0,
        ]);

        $observer = $this->observerWith($product, $quoteItem);
        $this->subject->execute($observer);
    }

    public function testExecuteSwallowsExceptionsAndLogsThem(): void
    {
        $this->config->shouldReceive('isAnalyticsEnabled')->once()->andThrow(new RuntimeException('boom'));
        $this->logger->shouldReceive('error')->once()->with(
            'Tweakwise add to cart event could not be stashed',
            ['message' => 'boom']
        );

        $observer = Mockery::mock(Observer::class);
        $observer->shouldNotReceive('getEvent');

        $this->subject->execute($observer);
    }
}
