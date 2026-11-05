<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model;

use Emico\CodeCept\Test\Unit;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Mockery;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use Tweakwise\Magento2Tweakwise\Block\Navigation\SearchBanner\SearchBannerRenderer;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\NavigationContext;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2Tweakwise\Model\Observer\CatalogLastPageRedirect;
use Tweakwise\Magento2Tweakwise\Model\Observer\CatalogNavigationLastPageRedirect;
use Tweakwise\Magento2Tweakwise\Model\Observer\CatalogSearchLastPageRedirect;

class PublicApiCompatibilityTest extends Unit
{
    public function testExistingConfigMethodsAllowUntypedOverrides(): void
    {
        $this->assertFalse((new ReflectionMethod(Config::class, 'getTimeout'))->hasReturnType());
        $this->assertFalse((new ReflectionMethod(Config::class, 'getTweakwiseExceptionTrown'))->hasReturnType());

        $config = new class extends Config {
            public function __construct()
            {
            }

            public function getTimeout()
            {
                return 7;
            }

            public function getTweakwiseExceptionTrown()
            {
                return true;
            }
        };

        $this->assertSame(7, $config->getTimeout());
        $this->assertTrue($config->getTweakwiseExceptionTrown());
    }

    public function testBannerAcceptsOriginalContextAndDataArguments(): void
    {
        $originalObjectManager = ObjectManager::getInstance();
        $objectManager = Mockery::mock(ObjectManagerInterface::class);
        $objectManager->shouldReceive('get')->once()->with(Config::class)->andReturn(Mockery::mock(Config::class));
        $objectManager->shouldReceive('get')->once()->with(LoggerInterface::class)
            ->andReturn(Mockery::mock(LoggerInterface::class));
        ObjectManager::setInstance($objectManager);

        try {
            $block = new SearchBannerRenderer(
                Mockery::mock(TemplateContext::class)->shouldIgnoreMissing(),
                ['legacy_data' => 'preserved']
            );

            $this->assertSame('preserved', $block->getData('legacy_data'));
        } finally {
            ObjectManager::setInstance($originalObjectManager);
        }
    }

    public function testRedirectObserversAcceptOriginalConstructorArguments(): void
    {
        $originalObjectManager = ObjectManager::getInstance();
        $logger = Mockery::mock(LoggerInterface::class);
        $objectManager = Mockery::mock(ObjectManagerInterface::class);
        $objectManager->shouldReceive('get')->times(3)->with(LoggerInterface::class)->andReturn($logger);
        ObjectManager::setInstance($objectManager);

        try {
            $config = Mockery::mock(Config::class);
            $navigationContext = Mockery::mock(NavigationContext::class);
            $actionContext = Mockery::mock(Context::class);

            $this->assertInstanceOf(
                CatalogLastPageRedirect::class,
                new CatalogLastPageRedirect($config, $navigationContext, $actionContext)
            );
            $this->assertInstanceOf(
                CatalogSearchLastPageRedirect::class,
                new CatalogSearchLastPageRedirect($config, $navigationContext, $actionContext)
            );
            $this->assertInstanceOf(
                CatalogNavigationLastPageRedirect::class,
                new CatalogNavigationLastPageRedirect(
                    $config,
                    $navigationContext,
                    $actionContext,
                    Mockery::mock(Registry::class)
                )
            );
        } finally {
            ObjectManager::setInstance($originalObjectManager);
        }
    }
}
