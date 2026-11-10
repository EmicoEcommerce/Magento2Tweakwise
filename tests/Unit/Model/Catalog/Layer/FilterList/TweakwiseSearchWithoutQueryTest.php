<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Catalog\Layer\FilterList;

use Emico\CodeCept\Test\Unit;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mockery;
use Mockery\MockInterface;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\FilterFactory;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\FilterList\Tweakwise;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\NavigationContext\CurrentContext;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\ProductSearchRequest;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Test\Support\UnitTester;

class TweakwiseSearchWithoutQueryTest extends Unit
{
    protected UnitTester $tester;

    private CurrentContext&MockInterface $currentContext;

    private Tweakwise $subject;

    protected function _before(): void
    {
        $request = Mockery::mock(ProductSearchRequest::class);
        $request->shouldReceive('hasParameter')->with('tn_q')->andReturn(false);

        $this->currentContext = Mockery::mock(CurrentContext::class);
        $this->currentContext->shouldReceive('getRequest')->once()->andReturn($request);
        $this->currentContext->shouldNotReceive('getResponse');

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

    public function testGetFiltersSkipsApiCallWhenSearchRequestHasNoSearchTerm(): void
    {
        $layer = Mockery::mock(Layer::class);

        $this->assertSame([], $this->subject->getFilters($layer));
    }
}
