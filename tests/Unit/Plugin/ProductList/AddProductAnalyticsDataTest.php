<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Plugin\ProductList;

use Emico\CodeCept\Test\Unit;
use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Catalog\Model\Product;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Tweakwise\Magento2Tweakwise\Model\Analytics\GroupedProductIdResolver;
use Tweakwise\Magento2Tweakwise\Model\Analytics\ProductKeyResolver;
use Tweakwise\Magento2Tweakwise\Model\PersonalMerchandisingConfig;
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
        $product->shouldReceive('getFinalPrice')->once()->andReturn('59.99');

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

    public function testAfterGetProductDetailsHtmlResolvesGroupedProductIdWhenGroupedProductsAreEnabled(): void
    {
        $this->tweakwiseConfig->shouldReceive('isAnalyticsEnabled')->once()->andReturn(true);
        $this->tweakwiseConfig->shouldReceive('isGroupedProductsEnabled')->once()->andReturn(true);

        $product = Mockery::mock(Product::class);
        $product->shouldReceive('getId')->andReturn(10);
        $product->shouldReceive('getFinalPrice')->once()->andReturn('10.00');

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
}
