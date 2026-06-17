<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Block\LayeredNavigation\RenderLayered;

use Emico\CodeCept\Test\Unit;
use Magento\Framework\Escaper;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Template\Context;
use PHPUnit\Framework\MockObject\MockObject;
use Tweakwise\Magento2Tweakwise\Block\LayeredNavigation\RenderLayered\DefaultRenderer;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Filter\Item;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2Tweakwise\Model\NavigationConfig;
use Tweakwise\Magento2Tweakwise\Model\Seo\FilterHelper;
use Tweakwise\Magento2TweakwiseExport\Model\Helper;
use Tweakwise\Test\Support\UnitTester;

class DefaultRendererTest extends Unit
{
    protected UnitTester $tester;

    /**
     * @return void
     */
    public function testGetCategoryUrlDoesNotPrependDomainWhenOnlySchemeDiffers(): void
    {
        $renderer = $this->createRendererWithBaseUrl('https://magento2.test/');

        $item = $this->createMock(Item::class);
        $item->method('getUrl')->willReturn('http://magento2.test/default/women/tops-women2/');

        $this->assertSame(
            'http://magento2.test/default/women/tops-women2/',
            $renderer->getCategoryUrl($item)
        );
    }

    /**
     * @return void
     */
    public function testGetCategoryUrlPrependsBaseUrlForRelativeFacetLink(): void
    {
        $renderer = $this->createRendererWithBaseUrl('https://magento2.test/');

        $item = $this->createMock(Item::class);
        $item->method('getUrl')->willReturn('/default/women/tops-women2/');

        $this->assertSame(
            'https://magento2.test//default/women/tops-women2/',
            $renderer->getCategoryUrl($item)
        );
    }

    /**
     * @param string $baseUrl
     * @return DefaultRenderer|MockObject
     */
    private function createRendererWithBaseUrl(string $baseUrl): DefaultRenderer|MockObject
    {
        $renderer = $this->getMockBuilder(DefaultRenderer::class)
            ->setConstructorArgs([
                $this->createMock(Context::class),
                $this->createMock(Config::class),
                $this->createMock(NavigationConfig::class),
                $this->createMock(FilterHelper::class),
                $this->createMock(Json::class),
                $this->createMock(Helper::class),
                $this->createMock(Escaper::class),
            ])
            ->onlyMethods(['getBaseUrl'])
            ->getMock();

        $renderer->method('getBaseUrl')->willReturn($baseUrl);

        return $renderer;
    }
}
