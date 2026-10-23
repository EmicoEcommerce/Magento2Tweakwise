<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Block\Navigation\SearchBanner;

use Emico\CodeCept\Test\Unit;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Mockery;
use Psr\Log\LoggerInterface;
use Tweakwise\Magento2Tweakwise\Block\Navigation\SearchBanner\SearchBannerRenderer;
use Tweakwise\Magento2Tweakwise\Exception\ApiException;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\NavigationContext;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\NavigationContext\CurrentContext;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2Tweakwise\Model\NavigationConfig;

class SearchBannerRendererTest extends Unit
{
    public function testUnreachableApiOmitsSearchBanners(): void
    {
        $context = Mockery::mock(NavigationContext::class);
        $context->shouldReceive('showSearchBanners')->andReturn(true);
        $context->shouldReceive('getResponse')->once()->andThrow(new ApiException('Gateways unreachable'));
        $currentContext = new CurrentContext();
        $currentContext->setContext($context);
        $navigationConfig = Mockery::mock(NavigationConfig::class);
        $navigationConfig->shouldReceive('getNavigationContext')->andReturn($currentContext);

        $config = Mockery::mock(Config::class);
        $config->shouldReceive('getTweakwiseExceptionThrown')->andReturn(false, true, true);
        $config->shouldReceive('setTweakwiseExceptionThrown')->once();
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('critical')->once()->with('Gateways unreachable');

        $block = new SearchBannerRenderer(
            Mockery::mock(TemplateContext::class)->shouldIgnoreMissing(),
            [],
            $config,
            $logger
        );
        $block->setData('tweakwise_navigation_context', $navigationConfig);

        $this->assertSame([], $block->getContainerTopBanner());
        $this->assertSame([], $block->getListTopBanner());
        $this->assertSame([], $block->getProductsTopBanner());
    }
}
