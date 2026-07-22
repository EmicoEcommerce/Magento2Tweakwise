<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Block\LayeredNavigation\RenderLayered;

use Emico\CodeCept\Test\Unit;
use Magento\Framework\Escaper;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Template\Context;
use Mockery;
use Tweakwise\Magento2Tweakwise\Block\LayeredNavigation\RenderLayered\LinkRenderer;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Filter;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Filter\Item;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\StrategyHelper;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\FacetType;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\FacetType\SettingsType;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2Tweakwise\Model\NavigationConfig;
use Tweakwise\Magento2Tweakwise\Model\Seo\FilterHelper;
use Tweakwise\Magento2TweakwiseExport\Model\Helper;
use Tweakwise\Test\Support\UnitTester;

class LinkRendererTest extends Unit
{
    protected UnitTester $tester;

    /**
     * @return void
     * phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
     */
    public function _after(): void
    {
        Mockery::close();
    }

    /**
     * @return void
     */
    public function testGetItemsWarmsStrategyHelperWithResolvedItemsAndStoreId(): void
    {
        $settings = Mockery::mock(SettingsType::class);
        $settings->shouldReceive('getSelectionType')->andReturn(SettingsType::SELECTION_TYPE_LINK);
        $settings->shouldReceive('getNumberOfShownAttributes')->andReturn(10);

        $facet = Mockery::mock(FacetType::class);
        $facet->shouldReceive('getFacetSettings')->andReturn($settings);

        $firstItem = Mockery::mock(Item::class);
        $firstItem->shouldReceive('setData')->with('_default_hidden', false)->andReturnSelf();
        $secondItem = Mockery::mock(Item::class);
        $secondItem->shouldReceive('setData')->with('_default_hidden', false)->andReturnSelf();
        $items = [$firstItem, $secondItem];

        $filter = Mockery::mock(Filter::class);
        $filter->shouldReceive('getItems')->andReturn($items);
        $filter->shouldReceive('getFacet')->andReturn($facet);
        $filter->shouldReceive('getStoreId')->andReturn(5);

        $config = Mockery::mock(Config::class);
        $config->shouldReceive('isCategoryViewDefault')->andReturn(false);

        $strategyHelper = Mockery::mock(StrategyHelper::class);
        $strategyHelper->shouldReceive('warmUp')->once()->with($items, 5);

        $renderer = new LinkRenderer(
            Mockery::mock(Context::class),
            $config,
            Mockery::mock(NavigationConfig::class),
            Mockery::mock(FilterHelper::class),
            Mockery::mock(Json::class),
            Mockery::mock(Helper::class),
            Mockery::mock(Escaper::class),
            $strategyHelper
        );
        $renderer->setFilter($filter);

        $result = $renderer->getItems();

        $this->assertSame($items, $result);
    }
}
