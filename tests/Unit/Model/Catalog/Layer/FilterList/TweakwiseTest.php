<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Catalog\Layer\FilterList;

use Emico\CodeCept\Test\Unit;
use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mockery;
use Mockery\MockInterface;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\FilterFactory;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\FilterList\Tweakwise;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\NavigationContext;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\NavigationContext\CurrentContext;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\ProductNavigationRequest;
use Tweakwise\Magento2Tweakwise\Model\Client\Response\ProductNavigationResponse;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Tweakwise\Test\Support\UnitTester;

class TweakwiseTest extends Unit
{
    protected UnitTester $tester;

    private CurrentContext&MockInterface $currentContext;

    private Tweakwise $subject;

    protected function _before(): void
    {
        $request = Mockery::mock(ProductNavigationRequest::class);
        $request->shouldReceive('hasParameter')->with('tn_cid')->andReturn(true);

        $response = Mockery::mock(ProductNavigationResponse::class);
        $response->shouldReceive('getFacets')->once()->andReturn(null);

        $navigationContext = Mockery::mock(NavigationContext::class);
        $navigationContext->shouldReceive('getFilterAttributeMap')->once()->with([])->andReturn([]);

        $this->currentContext = Mockery::mock(CurrentContext::class);
        $this->currentContext->shouldReceive('getRequest')->once()->andReturn($request);
        $this->currentContext->shouldReceive('getResponse')->once()->andReturn($response);
        $this->currentContext->shouldReceive('getContext')->once()->andReturn($navigationContext);

        $this->subject = new Tweakwise(
            Mockery::mock(FilterFactory::class),
            $this->currentContext,
            Mockery::mock(Config::class),
            Mockery::mock(AttributeFactory::class),
            Mockery::mock(StoreManagerInterface::class),
            Mockery::mock(CategoryRepositoryInterface::class),
            Mockery::mock(RequestInterface::class)
        );
    }

    protected function _after(): void
    {
        Mockery::close();
    }

    public function testGetFiltersReturnsEmptyArrayWhenResponseFacetsAreNull(): void
    {
        $layer = Mockery::mock(Layer::class);

        $this->assertSame([], $this->subject->getFilters($layer));
    }
}
