<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Observer;

use Emico\CodeCept\Test\Unit;
use Magento\Catalog\Model\Category;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\HTTP\PhpEnvironment\Response;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Mockery;
use Psr\Log\LoggerInterface;
use Tweakwise\Magento2Tweakwise\Exception\ApiException;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\NavigationContext;
use Tweakwise\Magento2Tweakwise\Model\Client\Request;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\ProductNavigationRequest;
use Tweakwise\Magento2Tweakwise\Model\Client\Response\ProductNavigationResponse;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2Tweakwise\Model\Observer\CatalogLastPageRedirect;
use Tweakwise\Magento2Tweakwise\Model\Observer\CatalogNavigationLastPageRedirect;
use Tweakwise\Magento2Tweakwise\Model\Observer\CatalogSearchLastPageRedirect;
use Tweakwise\Magento2TweakwiseExport\Model\Helper;
use Tweakwise\Test\Support\UnitTester;

class CatalogLastPageRedirectTest extends Unit
{
    protected UnitTester $tester;

    protected function _after(): void
    {
        Mockery::close();
    }

    /**
     * @dataProvider failureCodes
     */
    public function testInitialApiFailureDoesNotAbortPaginatedPage(int $code): void
    {
        $this->expectNotToPerformAssertions();

        $config = Mockery::mock(Config::class);
        $config->shouldReceive('getTweakwiseExceptionThrown')->once()->andReturn(false);
        if ($code === 404) {
            $config->shouldNotReceive('setTweakwiseExceptionThrown');
        } else {
            $config->shouldReceive('setTweakwiseExceptionThrown')->once();
        }

        $request = Mockery::mock(RequestInterface::class);
        $request->shouldReceive('getParam')->once()->with('p', 1)->andReturn(8);
        $response = Mockery::mock(Response::class);
        $response->shouldReceive('isRedirect')->once()->andReturn(false);
        $response->shouldNotReceive('setRedirect');

        $actionContext = Mockery::mock(Context::class);
        $actionContext->shouldReceive('getRequest')->once()->andReturn($request);
        $actionContext->shouldReceive('getResponse')->once()->andReturn($response);
        $actionContext->shouldNotReceive('getUrl');

        $navigationContext = Mockery::mock(NavigationContext::class);
        $navigationContext->shouldReceive('getResponse')->once()
            ->andThrow(new ApiException('Both gateways are unreachable', $code));
        $logger = Mockery::mock(LoggerInterface::class);
        if ($code === 404) {
            $logger->shouldNotReceive('critical');
        } else {
            $logger->shouldReceive('critical')->once()->with('Both gateways are unreachable');
        }

        $subject = new CatalogLastPageRedirect($config, $navigationContext, $actionContext, $logger);
        $subject->execute(new Observer());
    }

    public static function failureCodes(): array
    {
        return [[0], [404]];
    }

    public function testExecuteDoesNotCreateNavigationResponseOnFirstPage(): void
    {
        $this->expectNotToPerformAssertions();

        $request = Mockery::mock(RequestInterface::class);
        $request->shouldReceive('getParam')->once()->with('p', 1)->andReturn(1);

        $actionContext = Mockery::mock(Context::class);
        $actionContext->shouldReceive('getRequest')->once()->andReturn($request);
        $actionContext->shouldNotReceive('getResponse');

        $navigationContext = Mockery::mock(NavigationContext::class);
        $navigationContext->shouldNotReceive('getResponse');

        $subject = new CatalogLastPageRedirect(
            Mockery::mock(Config::class),
            $navigationContext,
            $actionContext,
            Mockery::mock(LoggerInterface::class)
        );

        $subject->execute(new Observer());
    }

    public function testEmptyCategoryBeyondFirstPageRedirectsToPageOne(): void
    {
        $config = Mockery::mock(Config::class);
        $config->shouldReceive('isLayeredEnabled')->once()->andReturn(true);
        $config->shouldReceive('getTweakwiseExceptionThrown')->once()->andReturn(false);

        $request = Mockery::mock(ProductNavigationRequest::class);
        $request->shouldReceive('hasParameter')->once()->with('tn_cid')->andReturn(true);

        $navigationContext = $this->createNavigationContext(0, 0, 8);
        $navigationContext->shouldReceive('getRequest')->once()->andReturn($request);

        $httpRequest = Mockery::mock(RequestInterface::class);
        $httpRequest->shouldReceive('getParam')->twice()->with('p', 1)->andReturn(8);

        $actionContext = Mockery::mock(Context::class);
        $actionContext->shouldReceive('getRequest')->twice()->andReturn($httpRequest);
        $this->expectRedirect($actionContext, 'https://example.com/category?p=1');

        $registry = Mockery::mock(Registry::class);
        $registry->shouldReceive('registry')->once()->with('current_category')->andReturn(Mockery::mock(Category::class));

        $subject = new CatalogNavigationLastPageRedirect(
            $config,
            $navigationContext,
            $actionContext,
            $registry,
            Mockery::mock(LoggerInterface::class)
        );

        $subject->execute(new Observer());
    }

    public function testEmptyCategoryOnFirstPageDoesNotCreateNavigationResponse(): void
    {
        $config = Mockery::mock(Config::class);
        $config->shouldReceive('isLayeredEnabled')->once()->andReturn(true);

        $httpRequest = Mockery::mock(RequestInterface::class);
        $httpRequest->shouldReceive('getParam')->once()->with('p', 1)->andReturn(1);

        $actionContext = Mockery::mock(Context::class);
        $actionContext->shouldReceive('getRequest')->once()->andReturn($httpRequest);
        $actionContext->shouldNotReceive('getResponse');

        $navigationContext = Mockery::mock(NavigationContext::class);
        $navigationContext->shouldNotReceive('getRequest');
        $navigationContext->shouldNotReceive('getResponse');

        $registry = Mockery::mock(Registry::class);
        $registry->shouldNotReceive('registry');

        $subject = new CatalogNavigationLastPageRedirect(
            $config,
            $navigationContext,
            $actionContext,
            $registry,
            Mockery::mock(LoggerInterface::class)
        );

        $subject->execute(new Observer());
    }

    public function testEmptySearchResultsStillDoNotRedirect(): void
    {
        $config = Mockery::mock(Config::class);
        $config->shouldReceive('isSearchEnabled')->once()->andReturn(true);
        $config->shouldReceive('getTweakwiseExceptionThrown')->once()->andReturn(false);

        $httpRequest = Mockery::mock(RequestInterface::class);
        $httpRequest->shouldReceive('getParam')->once()->with('p', 1)->andReturn(8);

        $httpResponse = Mockery::mock(Response::class);
        $httpResponse->shouldReceive('isRedirect')->once()->andReturn(false);
        $httpResponse->shouldNotReceive('setRedirect');

        $actionContext = Mockery::mock(Context::class);
        $actionContext->shouldReceive('getRequest')->once()->andReturn($httpRequest);
        $actionContext->shouldReceive('getResponse')->once()->andReturn($httpResponse);
        $actionContext->shouldNotReceive('getUrl');

        $subject = new CatalogSearchLastPageRedirect(
            $config,
            $this->createNavigationContext(0, 0, 8),
            $actionContext,
            Mockery::mock(LoggerInterface::class)
        );
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
