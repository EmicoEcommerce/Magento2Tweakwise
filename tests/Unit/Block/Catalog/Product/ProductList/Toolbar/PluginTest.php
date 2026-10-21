<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Block\Catalog\Product\ProductList\Toolbar;

use Closure;
use Emico\CodeCept\Test\Unit;
use Magento\Catalog\Block\Product\ProductList\Toolbar;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Mockery;
use Psr\Log\LoggerInterface;
use Tweakwise\Magento2Tweakwise\Exception\ApiException;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\NavigationContext\CurrentContext;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\ProductSearchRequest;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2Tweakwise\Block\Catalog\Product\ProductList\Toolbar\Plugin;

class PluginTest extends Unit
{
    public function testAvailableOrdersFallBackWhenExceptionFlagIsSet(): void
    {
        $config = Mockery::mock(Config::class);
        $config->shouldReceive('getTweakwiseExceptionTrown')->once()->andReturn(true);

        $context = Mockery::mock(CurrentContext::class);
        $context->shouldNotReceive('getRequest');
        $context->shouldNotReceive('getResponse');

        $plugin = new Plugin(
            $config,
            $context,
            Mockery::mock(StockConfigurationInterface::class),
            Mockery::mock(LoggerInterface::class)
        );
        $proceed = static fn (): array => ['position' => 'Position'];

        $result = $plugin->aroundGetAvailableOrders(
            Mockery::mock(Toolbar::class),
            Closure::fromCallable($proceed)
        );

        $this->assertSame(['position' => 'Position'], $result);
    }

    public function testAvailableOrdersFallBackWhenNavigationRequestFails(): void
    {
        $config = Mockery::mock(Config::class);
        $config->shouldReceive('isLayeredEnabled')->andReturn(false);
        $config->shouldReceive('isSearchEnabled')->andReturn(true);
        $config->shouldReceive('getTweakwiseExceptionTrown')->andReturn(false);
        $config->shouldReceive('setTweakwiseExceptionThrown')->once();

        $context = Mockery::mock(CurrentContext::class);
        $context->shouldReceive('getRequest')->andReturn(Mockery::mock(ProductSearchRequest::class));
        $context->shouldReceive('getResponse')->andThrow(new ApiException('Tweakwise API is unavailable'));

        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('critical')->once();

        $plugin = new Plugin(
            $config,
            $context,
            Mockery::mock(StockConfigurationInterface::class),
            $logger
        );
        $proceed = static fn (): array => ['position' => 'Position'];

        $result = $plugin->aroundGetAvailableOrders(
            Mockery::mock(Toolbar::class),
            Closure::fromCallable($proceed)
        );

        $this->assertSame(['position' => 'Position'], $result);
    }
}
