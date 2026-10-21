<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Block\Navigation\SearchBanner;

use Emico\CodeCept\Test\Unit;
use Mockery;
use Tweakwise\Magento2Tweakwise\Block\Navigation\SearchBanner\SearchBannerRenderer;
use Tweakwise\Magento2Tweakwise\Exception\ApiException;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\NavigationContext;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\NavigationContext\CurrentContext;
use Tweakwise\Magento2Tweakwise\Model\NavigationConfig;

class SearchBannerRendererTest extends Unit
{
    public function testUnreachableApiOmitsSearchBanners(): void
    {
        $context = Mockery::mock(NavigationContext::class);
        $context->shouldReceive('showSearchBanners')->andReturn(true);
        $context->shouldReceive('getResponse')->andThrow(new ApiException('Gateways unreachable'));
        $currentContext = new CurrentContext();
        $currentContext->setContext($context);
        $navigationConfig = Mockery::mock(NavigationConfig::class);
        $navigationConfig->shouldReceive('getNavigationContext')->andReturn($currentContext);

        $block = $this->getMockBuilder(SearchBannerRenderer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData'])
            ->getMock();
        $block->method('getData')->with('tweakwise_navigation_context', '')
            ->willReturn($navigationConfig);

        $this->assertSame([], $block->getContainerTopBanner());
        $this->assertSame([], $block->getListTopBanner());
        $this->assertSame([], $block->getProductsTopBanner());
    }
}
