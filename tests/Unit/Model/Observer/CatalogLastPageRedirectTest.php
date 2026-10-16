<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Observer;

use Emico\CodeCept\Test\Unit;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Event\Observer;
use Magento\Framework\HTTP\PhpEnvironment\Response;
use Magento\Framework\UrlInterface;
use Mockery;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\NavigationContext;
use Tweakwise\Magento2Tweakwise\Model\Client\Request;
use Tweakwise\Magento2Tweakwise\Model\Client\Response\ProductNavigationResponse;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2Tweakwise\Model\Observer\CatalogNavigationLastPageRedirect;
use Tweakwise\Magento2Tweakwise\Model\Observer\CatalogSearchLastPageRedirect;
use Tweakwise\Magento2TweakwiseExport\Model\Helper;
use Tweakwise\Test\Support\UnitTester;

class CatalogLastPageRedirectTest extends Unit
{
    protected UnitTester $tester;

    public function _after(): void
    {
        Mockery::close();
    }

    public function testEmptyCategoryBeyondFirstPageRedirectsToPageOne(): void
    {
        $config = Mockery::mock(Config::class);
        $config->shouldReceive('isLayeredEnabled')->once()->andReturn(true);
        $config->shouldReceive('getTweakwiseExceptionTrown')->once()->andReturn(false);

        $actionContext = Mockery::mock(Context::class);
        $this->expectRedirect($actionContext, 'https://example.com/category?p=1');

        $subject = new CatalogNavigationLastPageRedirect(
            $config,
            $this->createNavigationContext(0, 0, 8),
            $actionContext
        );

        $subject->execute(new Observer());
    }

    public function testEmptyCategoryOnFirstPageDoesNotRedirect(): void
    {
        $config = Mockery::mock(Config::class);
        $config->shouldReceive('isLayeredEnabled')->once()->andReturn(true);
        $config->shouldReceive('getTweakwiseExceptionTrown')->once()->andReturn(false);

        $httpResponse = Mockery::mock(Response::class);
        $httpResponse->shouldReceive('isRedirect')->once()->andReturn(false);
        $httpResponse->shouldNotReceive('setRedirect');

        $actionContext = Mockery::mock(Context::class);
        $actionContext->shouldReceive('getResponse')->once()->andReturn($httpResponse);
        $actionContext->shouldReceive('getUrl')->never();

        $subject = new CatalogNavigationLastPageRedirect(
            $config,
            $this->createNavigationContext(0, 0, 1),
            $actionContext
        );

        $subject->execute(new Observer());
    }

    public function testEmptySearchResultsStillDoNotRedirect(): void
    {
        $config = Mockery::mock(Config::class);
        $config->shouldReceive('isSearchEnabled')->once()->andReturn(true);
        $config->shouldReceive('getTweakwiseExceptionTrown')->once()->andReturn(false);

        $navigationContext = $this->createNavigationContext(0, 0, 8);

        $httpResponse = Mockery::mock(Response::class);
        $httpResponse->shouldReceive('isRedirect')->once()->andReturn(false);
        $httpResponse->shouldNotReceive('setRedirect');

        $actionContext = Mockery::mock(Context::class);
        $actionContext->shouldReceive('getResponse')->once()->andReturn($httpResponse);
        $actionContext->shouldNotReceive('getUrl');

        $subject = new CatalogSearchLastPageRedirect($config, $navigationContext, $actionContext);
        $subject->execute(new Observer());
    }

    private function expectRedirect(Context $actionContext, string $url): void
    {
        $httpResponse = Mockery::mock(Response::class);
        $httpResponse->shouldReceive('isRedirect')->once()->andReturn(false);
        $httpResponse->shouldReceive('setRedirect')->once()->with($url);

        $urlBuilder = Mockery::mock(UrlInterface::class);
        $urlBuilder->shouldReceive('getUrl')
            ->once()
            ->with('*/*/*', [
                '_current' => true,
                '_use_rewrite' => true,
                '_query' => ['p' => 1],
            ])
            ->andReturn($url);

        $actionContext->shouldReceive('getResponse')->once()->andReturn($httpResponse);
        $actionContext->shouldReceive('getUrl')->once()->andReturn($urlBuilder);
    }

    private function createNavigationContext(int $numberOfItems, int $numberOfPages, int $currentPage): NavigationContext
    {
        $response = new ProductNavigationResponse(
            Mockery::mock(Helper::class),
            Mockery::mock(Request::class),
            []
        );
        $response->setProperties([
            'nrofitems' => $numberOfItems,
            'nrofpages' => $numberOfPages,
            'currentpage' => $currentPage,
        ]);

        $navigationContext = Mockery::mock(NavigationContext::class);
        $navigationContext->shouldReceive('getResponse')->once()->andReturn($response);

        return $navigationContext;
    }
}
