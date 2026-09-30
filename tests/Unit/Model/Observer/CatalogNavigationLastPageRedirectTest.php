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
use Mockery;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\NavigationContext;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\ProductNavigationRequest;
use Tweakwise\Magento2Tweakwise\Model\Client\Response\ProductNavigationResponse;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\PropertiesType;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2Tweakwise\Model\Observer\CatalogNavigationLastPageRedirect;
use Tweakwise\Test\Support\UnitTester;

class CatalogNavigationLastPageRedirectTest extends Unit
{
    protected UnitTester $tester;

    protected function _after(): void
    {
        Mockery::close();
    }

    public function testCategoryIsAddedBeforePaginatedResponseIsCreated(): void
    {
        $category = Mockery::mock(Category::class);

        $request = Mockery::mock(ProductNavigationRequest::class);
        $request->shouldReceive('hasParameter')->once()->with('tn_cid')->andReturn(false);
        $request->shouldReceive('addCategoryFilter')->once()->with($category)->ordered()->andReturnSelf();

        $properties = Mockery::mock(PropertiesType::class);
        $properties->shouldReceive('getNumberOfItems')->once()->andReturn(12);
        $properties->shouldReceive('getNumberOfPages')->once()->andReturn(3);
        $properties->shouldReceive('getCurrentPage')->once()->andReturn(2);

        $navigationResponse = Mockery::mock(ProductNavigationResponse::class);
        $navigationResponse->shouldReceive('getProperties')->once()->andReturn($properties);

        $navigationContext = Mockery::mock(NavigationContext::class);
        $navigationContext->shouldReceive('getRequest')->once()->andReturn($request);
        $navigationContext->shouldReceive('getResponse')->once()->ordered()->andReturn($navigationResponse);

        $httpRequest = Mockery::mock(RequestInterface::class);
        $httpRequest->shouldReceive('getParam')->once()->with('p', 1)->andReturn(2);

        $httpResponse = Mockery::mock(Response::class);
        $httpResponse->shouldReceive('isRedirect')->once()->andReturn(false);

        $actionContext = Mockery::mock(Context::class);
        $actionContext->shouldReceive('getRequest')->once()->andReturn($httpRequest);
        $actionContext->shouldReceive('getResponse')->once()->andReturn($httpResponse);

        $config = Mockery::mock(Config::class);
        $config->shouldReceive('isLayeredEnabled')->once()->andReturn(true);
        $config->shouldReceive('getTweakwiseExceptionTrown')->once()->andReturn(false);

        $registry = Mockery::mock(Registry::class);
        $registry->shouldReceive('registry')->once()->with('current_category')->andReturn($category);

        $subject = new CatalogNavigationLastPageRedirect(
            $config,
            $navigationContext,
            $actionContext,
            $registry
        );

        $subject->execute(new Observer());

        $this->assertTrue(true);
    }
}
