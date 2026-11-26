<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Catalog\Layer;

use Emico\CodeCept\Test\Unit;
use Magento\Catalog\Helper\Product\ProductList;
use Magento\Catalog\Model\Layer\FilterableAttributeListInterface;
use Magento\Catalog\Model\Product\ProductList\Toolbar as ToolbarModel;
use Magento\Catalog\Model\Product\Visibility;
use Mockery;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\NavigationContext;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\NavigationContext\CurrentContext;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url;
use Tweakwise\Magento2Tweakwise\Model\Client;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\ProductNavigationRequest;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\ProductSearchRequest;
use Tweakwise\Magento2Tweakwise\Model\Client\RequestFactory;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2TweakwiseExport\Model\ProductAttributes;

class NavigationContextSearchBannersTest extends Unit
{
    /**
     * @dataProvider searchBannerCases
     */
    public function testShowSearchBanners(
        bool $searchEnabled,
        bool $searchBannersActive,
        bool $assistantEnabled,
        bool $layeredEnabled,
        string $requestClass,
        bool $expected
    ): void {
        $config = Mockery::mock(Config::class);
        $config->shouldReceive('isSearchEnabled')->andReturn($searchEnabled);
        $config->shouldReceive('isSearchBannersActive')->andReturn($searchBannersActive);
        $config->shouldReceive('isAssistantEnabled')->andReturn($assistantEnabled);
        $config->shouldReceive('isLayeredEnabled')->andReturn($layeredEnabled);

        $requestFactory = Mockery::mock(RequestFactory::class);
        $requestFactory->shouldReceive('create')->andReturn(Mockery::mock($requestClass));

        $context = new NavigationContext(
            $config,
            $requestFactory,
            Mockery::mock(Client::class),
            Mockery::mock(Url::class),
            Mockery::mock(FilterableAttributeListInterface::class),
            new CurrentContext(),
            Mockery::mock(ProductList::class),
            Mockery::mock(ToolbarModel::class),
            Mockery::mock(ProductAttributes::class),
            Mockery::mock(Visibility::class)
        );

        $this->assertSame($expected, $context->showSearchBanners());
    }

    public static function searchBannerCases(): array
    {
        return [
            'search page, banners enabled' => [true, true, false, false, ProductSearchRequest::class, true],
            'search page, banners disabled' => [true, false, true, true, ProductSearchRequest::class, false],
            'native search page without Tweakwise search' => [false, false, true, true, ProductSearchRequest::class, false],
            'assistant category search without Tweakwise search' => [
                false, false, true, true, ProductNavigationRequest::class, true
            ],
            'assistant category search, banners enabled' => [true, true, true, true, ProductNavigationRequest::class, true],
            'assistant category search, banners disabled' => [
                true, false, true, true, ProductNavigationRequest::class, false
            ],
            'category without assistant' => [true, true, false, true, ProductNavigationRequest::class, false],
            'category without Tweakwise navigation' => [false, false, true, false, ProductNavigationRequest::class, false],
        ];
    }
}
