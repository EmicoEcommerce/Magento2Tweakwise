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
use Tweakwise\Magento2Tweakwise\Model\Client\Request\ProductNavigationRequest;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\ProductSearchRequest;
use Tweakwise\Magento2Tweakwise\Model\Client\Response\ProductNavigationResponse;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2Tweakwise\Model\NavigationConfig;

class SearchBannerRendererTest extends Unit
{
    /**
     * @dataProvider failureCodes
     */
    public function testUnreachableApiOmitsSearchBanners(int $code): void
    {
        $context = Mockery::mock(NavigationContext::class);
        $context->shouldReceive('showSearchBanners')->andReturn(true);
        $context->shouldReceive('getResponse')->times($code === 404 ? 3 : 1)
            ->andThrow(new ApiException('Gateways unreachable', $code));
        $currentContext = new CurrentContext();
        $currentContext->setContext($context);
        $navigationConfig = Mockery::mock(NavigationConfig::class);
        $navigationConfig->shouldReceive('getNavigationContext')->andReturn($currentContext);

        $config = Mockery::mock(Config::class);
        $logger = Mockery::mock(LoggerInterface::class);
        if ($code === 404) {
            $config->shouldReceive('getTweakwiseExceptionThrown')->andReturn(false);
            $config->shouldNotReceive('setTweakwiseExceptionThrown');
            $logger->shouldNotReceive('critical');
        } else {
            $config->shouldReceive('getTweakwiseExceptionThrown')->andReturn(false, true, true);
            $config->shouldReceive('setTweakwiseExceptionThrown')->once();
            $logger->shouldReceive('critical')->once()->with('Gateways unreachable');
        }

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

    /**
     * @dataProvider bannerRequests
     */
    public function testBannersOnlyForSearchRequests(string $requestClass, ?string $query, bool $expected): void
    {
        $request = Mockery::mock($requestClass);
        $request->shouldReceive('getParameter')->with('tn_q')->andReturn($query);
        $banner = ['location' => 'ListTop', 'imageurl' => 'https://example.com/banner.jpg'];
        $response = Mockery::mock(ProductNavigationResponse::class);
        $response->shouldReceive('getValue')->with('searchbanners')->andReturn(['searchbanner' => [$banner]]);

        $context = Mockery::mock(NavigationContext::class);
        $context->shouldReceive('showSearchBanners')->andReturn(true);
        $context->shouldReceive('getResponse')->andReturn($response);
        $context->shouldReceive('getRequest')->andReturn($request);
        $currentContext = new CurrentContext();
        $currentContext->setContext($context);
        $navigationConfig = Mockery::mock(NavigationConfig::class);
        $navigationConfig->shouldReceive('getNavigationContext')->andReturn($currentContext);

        $config = Mockery::mock(Config::class);
        $config->shouldReceive('getTweakwiseExceptionThrown')->andReturn(false);

        $block = new SearchBannerRenderer(
            Mockery::mock(TemplateContext::class)->shouldIgnoreMissing(),
            [],
            $config,
            Mockery::mock(LoggerInterface::class)
        );
        $block->setData('tweakwise_navigation_context', $navigationConfig);

        $this->assertSame($expected ? [$banner] : [], $block->getListTopBanner());
    }

    public static function bannerRequests(): array
    {
        return [
            'search page' => [ProductSearchRequest::class, 'jacket', true],
            'assistant search within a category' => [ProductNavigationRequest::class, 'jacket', true],
            'normal category page' => [ProductNavigationRequest::class, null, false],
            'category with an empty search term' => [ProductNavigationRequest::class, '', false],
        ];
    }

    public static function failureCodes(): array
    {
        return [[0], [404]];
    }
}
