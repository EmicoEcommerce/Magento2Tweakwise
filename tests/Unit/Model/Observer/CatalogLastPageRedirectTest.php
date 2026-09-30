<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Observer;

use Emico\CodeCept\Test\Unit;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Mockery;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\NavigationContext;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2Tweakwise\Model\Observer\CatalogLastPageRedirect;
use Tweakwise\Test\Support\UnitTester;

class CatalogLastPageRedirectTest extends Unit
{
    protected UnitTester $tester;

    protected function _after(): void
    {
        Mockery::close();
    }

    public function testExecuteDoesNotCreateNavigationResponseOnFirstPage(): void
    {
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
            $actionContext
        );

        $subject->execute(new Observer());

        $this->assertTrue(true);
    }
}
