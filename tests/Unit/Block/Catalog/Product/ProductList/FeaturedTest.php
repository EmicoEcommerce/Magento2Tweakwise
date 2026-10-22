<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Block\Catalog\Product\ProductList;

use Emico\CodeCept\Test\Unit;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tweakwise\Magento2Tweakwise\Block\Catalog\Product\ProductList\Featured;
use Tweakwise\Magento2Tweakwise\Block\Catalog\Product\Widget\FeaturedProducts;
use Tweakwise\Magento2Tweakwise\Helper\Cache;
use Tweakwise\Test\Support\UnitTester;

class FeaturedTest extends Unit
{
    use MockeryPHPUnitIntegration;

    protected UnitTester $tester;

    /**
     * @return void
     */
    public function testGetTemplateReturnsModuleTemplateWhenMerchandisingListingShouldBeUsed(): void
    {
        $this->assertSame(
            'Tweakwise_Magento2Tweakwise::product/list/items.phtml',
            $this->createFeatured(true, false)->getTemplate()
        );
    }

    /**
     * @return void
     */
    public function testGetTemplateKeepsOriginalTemplateWhenMerchandisingListingShouldNotBeUsed(): void
    {
        $featured = $this->createFeatured(false, false);
        $featured->setTemplate('Magento_Catalog::product/list/items.phtml');

        $this->assertSame('Magento_Catalog::product/list/items.phtml', $featured->getTemplate());
    }

    /**
     * @return void
     */
    public function testGetTemplateKeepsOriginalTemplateOnHyvaTheme(): void
    {
        $featured = $this->createFeatured(true, true);
        $featured->setTemplate('Magento_Catalog::product/list/items.phtml');

        $this->assertSame('Magento_Catalog::product/list/items.phtml', $featured->getTemplate());
    }

    /**
     * @return void
     */
    public function testWidgetReturnsHyvaSliderTemplateOnHyvaTheme(): void
    {
        $this->assertSame(
            'Tweakwise_TweakwiseHyva::product/slider/product-slider.phtml',
            $this->createFeatured(true, true, FeaturedProducts::class)->getTemplate()
        );
    }

    /**
     * @return void
     */
    public function testWidgetReturnsModuleTemplateOnLuma(): void
    {
        $this->assertSame(
            'Tweakwise_Magento2Tweakwise::product/list/items.phtml',
            $this->createFeatured(true, false, FeaturedProducts::class)->getTemplate()
        );
    }

    /**
     * @return void
     */
    public function testWidgetKeepsOriginalTemplateOnHyvaThemeWhenMerchandisingListingShouldNotBeUsed(): void
    {
        $this->assertSame(
            'Magento_Catalog::product/list/items.phtml',
            $this->createFeatured(false, true, FeaturedProducts::class)->getTemplate()
        );
    }

    /**
     * @param bool $shouldUseMerchandisingListing
     * @param bool $isHyvaTheme
     * @param string $class
     * @return Featured
     */
    private function createFeatured(
        bool $shouldUseMerchandisingListing,
        bool $isHyvaTheme,
        string $class = Featured::class
    ): Featured {
        $cacheHelper = Mockery::mock(Cache::class);
        $cacheHelper->shouldReceive('shouldUseMerchandisingListing')->andReturn($shouldUseMerchandisingListing);
        $cacheHelper->shouldReceive('isHyvaTheme')->andReturn($isHyvaTheme);

        $this->tester->mockService(Cache::class, $cacheHelper);

        return $this->tester->getObjectManager()->create($class);
    }
}
