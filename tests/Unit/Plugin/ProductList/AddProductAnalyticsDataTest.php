<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Plugin\ProductList;

use Emico\CodeCept\Test\Unit;
use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type\Price as ProductPriceModel;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Model\Quote\Item;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Tweakwise\Magento2Tweakwise\Model\Analytics\CheckoutSessionDataProvider;
use Tweakwise\Magento2Tweakwise\Model\Analytics\GroupedProductIdResolver;
use Tweakwise\Magento2Tweakwise\Model\Analytics\ProductKeyResolver;
use Tweakwise\Magento2Tweakwise\Model\PersonalMerchandisingConfig;
use Tweakwise\Magento2Tweakwise\Observer\Event\StashAddToCartEvent;
use Tweakwise\Magento2Tweakwise\Plugin\ProductList\AddProductAnalyticsData;

class AddProductAnalyticsDataTest extends Unit
{
    use MockeryPHPUnitIntegration;

    private PersonalMerchandisingConfig&MockInterface $tweakwiseConfig;
    private ProductKeyResolver&MockInterface $productKeyResolver;
    private GroupedProductIdResolver&MockInterface $groupedProductIdResolver;
    private Json&MockInterface $jsonSerializer;
    private AbstractProduct&MockInterface $subjectBlock;
    private AddProductAnalyticsData $subject;

    protected function _before(): void
    {
        $this->tweakwiseConfig = Mockery::mock(PersonalMerchandisingConfig::class);
        $this->productKeyResolver = Mockery::mock(ProductKeyResolver::class);
        $this->groupedProductIdResolver = Mockery::mock(GroupedProductIdResolver::class);
        $this->jsonSerializer = Mockery::mock(Json::class);
        $this->subjectBlock = Mockery::mock(AbstractProduct::class);

        $store = Mockery::mock(StoreInterface::class);
        $store->shouldReceive('getId')->andReturn(1);
        $storeManager = Mockery::mock(StoreManagerInterface::class);
        $storeManager->shouldReceive('getStore')->andReturn($store);

        $this->subject = new AddProductAnalyticsData(
            $this->tweakwiseConfig,
            $storeManager,
            $this->productKeyResolver,
            $this->groupedProductIdResolver,
            $this->jsonSerializer
        );
    }

    public function testAfterGetProductDetailsHtmlReturnsHtmlUnchangedWhenAnalyticsIsDisabled(): void
    {
        $this->tweakwiseConfig->shouldReceive('isAnalyticsEnabled')->once()->andReturn(false);
        $this->productKeyResolver->shouldNotReceive('resolve');

        $product = Mockery::mock(Product::class);
        $result = $this->subject->afterGetProductDetailsHtml($this->subjectBlock, '<div>original</div>', $product);

        $this->assertSame('<div>original</div>', $result);
    }

    public function testAfterGetProductDetailsHtmlAppendsProductDataKeyedByMagentoIdWhenGroupedProductsAreDisabled(): void
    {
        $this->tweakwiseConfig->shouldReceive('isAnalyticsEnabled')->once()->andReturn(true);
        $this->tweakwiseConfig->shouldReceive('isGroupedProductsEnabled')->once()->andReturn(false);
        $this->groupedProductIdResolver->shouldNotReceive('resolve');

        $product = Mockery::mock(Product::class);
        $product->shouldReceive('getId')->andReturn(42);
        $product->shouldReceive('getData')->once()->with('tw_id')->andReturn(null);

        $priceModel = Mockery::mock(ProductPriceModel::class);
        $priceModel->shouldReceive('getFinalPrice')->once()->with(null, $product)->andReturn('59.99');
        $product->shouldReceive('getPriceModel')->once()->andReturn($priceModel);

        $this->productKeyResolver->shouldReceive('resolve')->once()->with('42', 1, false)->andReturn('10001042');
        $this->jsonSerializer->shouldReceive('serialize')->once()
            ->with(['productKey' => '10001042', 'price' => 59.99])
            ->andReturn('{"productKey":"10001042","price":59.99}');

        $result = $this->subject->afterGetProductDetailsHtml($this->subjectBlock, '<div>original</div>', $product);

        $this->assertSame(
            '<div>original</div>'
            . '<script>(window.tweakwiseListingProductData = window.tweakwiseListingProductData || {})'
            . '[42] = {"productKey":"10001042","price":59.99};</script>',
            $result
        );
    }

    public function testAfterGetProductDetailsHtmlFallsBackToGroupedProductIdResolverWhenTweakwiseHasNotMatchedAChild(): void
    {
        $this->tweakwiseConfig->shouldReceive('isAnalyticsEnabled')->once()->andReturn(true);
        $this->tweakwiseConfig->shouldReceive('isGroupedProductsEnabled')->once()->andReturn(true);

        $product = Mockery::mock(Product::class);
        $product->shouldReceive('getId')->andReturn(10);
        $product->shouldReceive('getData')->once()->with('tw_id')->andReturn(null);

        $priceModel = Mockery::mock(ProductPriceModel::class);
        $priceModel->shouldReceive('getFinalPrice')->once()->with(null, $product)->andReturn('10.00');
        $product->shouldReceive('getPriceModel')->once()->andReturn($priceModel);

        $this->groupedProductIdResolver->shouldReceive('resolve')->once()->with($product)->andReturn('11-10');
        $this->productKeyResolver->shouldReceive('resolve')->once()->with('11-10', 1, true)->andReturn('K11-K10');
        $this->jsonSerializer->shouldReceive('serialize')->once()
            ->with(['productKey' => 'K11-K10', 'price' => 10.0])
            ->andReturn('{"productKey":"K11-K10","price":10}');

        $result = $this->subject->afterGetProductDetailsHtml($this->subjectBlock, '', $product);

        $this->assertSame(
            '<script>(window.tweakwiseListingProductData = window.tweakwiseListingProductData || {})'
            . '[10] = {"productKey":"K11-K10","price":10};</script>',
            $result
        );
    }

    public function testAfterGetProductDetailsHtmlPrefersTheChildTweakwiseMatchedAgainstTheActiveFilterContext(): void
    {
        $this->tweakwiseConfig->shouldReceive('isAnalyticsEnabled')->once()->andReturn(true);
        $this->tweakwiseConfig->shouldReceive('isGroupedProductsEnabled')->once()->andReturn(true);

        $product = Mockery::mock(Product::class);
        $product->shouldReceive('getId')->andReturn(10);
        $product->shouldReceive('getData')->once()->with('tw_id')->andReturn(21);

        $priceModel = Mockery::mock(ProductPriceModel::class);
        $priceModel->shouldReceive('getFinalPrice')->once()->with(null, $product)->andReturn('10.00');
        $product->shouldReceive('getPriceModel')->once()->andReturn($priceModel);

        $this->groupedProductIdResolver->shouldNotReceive('resolve');
        $this->productKeyResolver->shouldReceive('resolve')->once()->with('21-10', 1, true)->andReturn('K21-K10');
        $this->jsonSerializer->shouldReceive('serialize')->once()
            ->with(['productKey' => 'K21-K10', 'price' => 10.0])
            ->andReturn('{"productKey":"K21-K10","price":10}');

        $result = $this->subject->afterGetProductDetailsHtml($this->subjectBlock, '', $product);

        $this->assertSame(
            '<script>(window.tweakwiseListingProductData = window.tweakwiseListingProductData || {})'
            . '[10] = {"productKey":"K21-K10","price":10};</script>',
            $result
        );
    }

    /**
     * Regression test: listing products are collection-loaded with the price index joined, so their
     * 'final_price' data attribute can be stale relative to a live recalculation (e.g. under
     * schedule-based indexing, or between catalog price rule runs). If the plugin read that attribute
     * directly, it could push a different price than StashAddToCartEvent (which always computes live
     * on a freshly loaded product) - breaking push.js's dedupe hash and duplicating the event at
     * Tweakwise. This asserts we go through getPriceModel() instead of the indexed attribute.
     */
    public function testAfterGetProductDetailsHtmlIgnoresTheIndexedFinalPriceAttributeInFavourOfALiveCalculation(): void
    {
        $this->tweakwiseConfig->shouldReceive('isAnalyticsEnabled')->once()->andReturn(true);
        $this->tweakwiseConfig->shouldReceive('isGroupedProductsEnabled')->once()->andReturn(false);

        $product = Mockery::mock(Product::class);
        $product->shouldReceive('getId')->andReturn(42);
        $product->shouldReceive('getData')->once()->with('tw_id')->andReturn(null);
        // Stale indexed value (e.g. a price rule that just ended) - the plugin must never read this.
        $product->shouldReceive('getData')->with('final_price')->andReturn('62.50');

        $priceModel = Mockery::mock(ProductPriceModel::class);
        $priceModel->shouldReceive('getFinalPrice')->once()->with(null, $product)->andReturn('59.99');
        $product->shouldReceive('getPriceModel')->once()->andReturn($priceModel);

        $this->productKeyResolver->shouldReceive('resolve')->once()->with('42', 1, false)->andReturn('10001042');
        $this->jsonSerializer->shouldReceive('serialize')->once()
            ->with(['productKey' => '10001042', 'price' => 59.99])
            ->andReturn('{"productKey":"10001042","price":59.99}');

        $result = $this->subject->afterGetProductDetailsHtml($this->subjectBlock, '', $product);

        $this->assertStringContainsString('"price":59.99', $result);
    }

    /**
     * Cross-checks the listing plugin against the cart observer for the same product/qty: the instant
     * client-side push and the stashed server-side push must agree on price, otherwise push.js's
     * dedupe hash mismatches and Tweakwise receives the addtocart event twice.
     */
    public function testListingPushPriceMatchesTheStashedServerEventPriceForTheSameProduct(): void
    {
        $this->tweakwiseConfig->shouldReceive('isAnalyticsEnabled')->once()->andReturn(true);
        $this->tweakwiseConfig->shouldReceive('isGroupedProductsEnabled')->once()->andReturn(false);
        $this->groupedProductIdResolver->shouldNotReceive('resolve');

        $listingProduct = Mockery::mock(Product::class);
        $listingProduct->shouldReceive('getId')->andReturn(42);
        $listingProduct->shouldReceive('getData')->once()->with('tw_id')->andReturn(null);

        $priceModel = Mockery::mock(ProductPriceModel::class);
        $priceModel->shouldReceive('getFinalPrice')->once()->with(null, $listingProduct)->andReturn('59.99');
        $listingProduct->shouldReceive('getPriceModel')->once()->andReturn($priceModel);

        $this->productKeyResolver->shouldReceive('resolve')->twice()->with('42', 1, false)->andReturn('10001042');
        $this->jsonSerializer->shouldReceive('serialize')->once()
            ->with(['productKey' => '10001042', 'price' => 59.99])
            ->andReturn('{"productKey":"10001042","price":59.99}');

        $listingHtml = $this->subject->afterGetProductDetailsHtml($this->subjectBlock, '', $listingProduct);

        // Server side: same product/qty, loaded fresh (no index) for checkout_cart_product_add_after.
        $observerConfig = Mockery::mock(PersonalMerchandisingConfig::class);
        $observerConfig->shouldReceive('isAnalyticsEnabled')->once()->andReturn(true);
        $observerConfig->shouldReceive('isGroupedProductsEnabled')->once()->andReturn(false);

        $checkoutSessionDataProvider = Mockery::mock(CheckoutSessionDataProvider::class);
        $checkoutSessionDataProvider->shouldReceive('add')->once()->with('addtocart_event', [
            'productKey' => '10001042',
            'quantity' => 1.0,
            'totalAmount' => 59.99,
        ]);

        $observerStoreManager = Mockery::mock(StoreManagerInterface::class);
        $observerStore = Mockery::mock(StoreInterface::class);
        $observerStore->shouldReceive('getId')->andReturn(1);
        $observerStoreManager->shouldReceive('getStore')->andReturn($observerStore);

        $stashObserver = new StashAddToCartEvent(
            $observerConfig,
            Mockery::mock(LoggerInterface::class),
            $this->productKeyResolver,
            $observerStoreManager,
            $checkoutSessionDataProvider
        );

        $observerProduct = Mockery::mock(Product::class);
        $observerProduct->shouldReceive('getFinalPrice')->andReturn(59.99);

        $quoteItem = Mockery::mock(Item::class);
        $quoteItem->shouldReceive('getQtyToAdd')->andReturn(1.0);
        $quoteItem->shouldReceive('getProductId')->andReturn(42);

        $event = Mockery::mock(Event::class);
        $event->shouldReceive('getProduct')->andReturn($observerProduct);
        $event->shouldReceive('getQuoteItem')->andReturn($quoteItem);
        $observer = Mockery::mock(Observer::class);
        $observer->shouldReceive('getEvent')->andReturn($event);

        $stashObserver->execute($observer);

        // Both payloads carry price 59.99 for the same product/qty, so push.js's payload hash matches
        // and the dedupe guard suppresses the second (stashed) push - no duplicate at Tweakwise.
        $this->assertStringContainsString('"price":59.99', $listingHtml);
    }
}
