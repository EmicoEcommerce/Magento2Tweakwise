<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Block\Cart\Crosssell;

use Emico\CodeCept\Test\Unit;
use Magento\Checkout\Block\Cart\Crosssell;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tweakwise\Magento2Tweakwise\Block\Cart\Crosssell\Plugin;
use Tweakwise\Magento2Tweakwise\Helper\Cache;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2Tweakwise\ViewModel\LinkedProductListItem;
use Tweakwise\Test\Support\UnitTester;

class PluginTest extends Unit
{
    use MockeryPHPUnitIntegration;

    protected UnitTester $tester;

    /**
     * @return void
     */
    public function testAfterGetTemplateReturnsModuleTemplateAndSetsViewModel(): void
    {
        $crosssell = Mockery::mock(Crosssell::class);
        $crosssell->shouldReceive('setData')
            ->once()
            ->with('view_model_linked_product_list_item', Mockery::type(LinkedProductListItem::class));

        $this->assertSame(
            'Tweakwise_Magento2Tweakwise::product/list/items.phtml',
            $this->createPlugin(true, true, false)->afterGetTemplate($crosssell, 'Magento_Catalog::product/list/items.phtml')
        );
    }

    /**
     * @return void
     */
    public function testAfterGetTemplateKeepsOriginalTemplateWhenRecommendationsAreDisabled(): void
    {
        $this->assertOriginalTemplateIsKept($this->createPlugin(false, true, false));
    }

    /**
     * @return void
     */
    public function testAfterGetTemplateKeepsOriginalTemplateWhenMerchandisingListingIsNotUsed(): void
    {
        $this->assertOriginalTemplateIsKept($this->createPlugin(true, false, false));
    }

    /**
     * @return void
     */
    public function testAfterGetTemplateKeepsOriginalTemplateOnHyvaTheme(): void
    {
        $this->assertOriginalTemplateIsKept($this->createPlugin(true, true, true));
    }

    /**
     * @param Plugin $plugin
     * @return void
     */
    private function assertOriginalTemplateIsKept(Plugin $plugin): void
    {
        $crosssell = Mockery::mock(Crosssell::class);
        $crosssell->shouldNotReceive('setData');

        $this->assertSame(
            'Magento_Catalog::product/list/items.phtml',
            $plugin->afterGetTemplate($crosssell, 'Magento_Catalog::product/list/items.phtml')
        );
    }

    /**
     * @param bool $recommendationsEnabled
     * @param bool $shouldUseMerchandisingListing
     * @param bool $isHyvaTheme
     * @return Plugin
     */
    private function createPlugin(
        bool $recommendationsEnabled,
        bool $shouldUseMerchandisingListing,
        bool $isHyvaTheme
    ): Plugin {
        $config = Mockery::mock(Config::class);
        $config->shouldReceive('isRecommendationsEnabled')->andReturn($recommendationsEnabled);

        $cacheHelper = Mockery::mock(Cache::class);
        $cacheHelper->shouldReceive('shouldUseMerchandisingListing')->andReturn($shouldUseMerchandisingListing);
        $cacheHelper->shouldReceive('isHyvaTheme')->andReturn($isHyvaTheme);

        $this->tester->mockService(Config::class, $config);
        $this->tester->mockService(Cache::class, $cacheHelper);

        return $this->tester->getObjectManager()->create(Plugin::class);
    }
}
