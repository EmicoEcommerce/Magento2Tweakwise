<?php

declare(strict_types=1);

namespace Tweakwise\Test\Functional;

use Codeception\Attribute\DataProvider;
use Emico\CodeCept\Test\Unit;
use Magento\Framework\App\Area;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ViewInterface;
use Magento\Framework\Serialize\Serializer\Base64Json;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\DesignInterface;
use Magento\LayeredNavigation\Block\Navigation;
use Magento\PageCache\Controller\Block\Esi;
use Mockery;
use Tweakwise\Magento2Tweakwise\Model\Client;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\ProductSearchRequest;
use Tweakwise\Magento2Tweakwise\Model\Client\Response\ProductNavigationResponse;
use Tweakwise\Magento2Tweakwise\Model\Config\Source\UrlStrategy;
use Tweakwise\Magento2Tweakwise\Observer\RemoveEsiNavigation;
use Tweakwise\Magento2TweakwiseExport\Model\Logger;
use Tweakwise\Test\Support\FunctionalTester;

/**
 * @see RemoveEsiNavigation
 */
class EsiNavigationTest extends Unit
{
    protected FunctionalTester $tester;

    protected function _before(): void
    {
        parent::_before();
        $this->tester->amOnStore('default', Area::AREA_FRONTEND);
        $this->tester->getObjectManager()->get(DesignInterface::class)
            ->setDesignTheme('Magento/blank', Area::AREA_FRONTEND);
        $this->tester->mockConfig('tweakwise/layered/enabled', 1);
        $this->tester->mockConfig('tweakwise/search/enabled', 1);

        $logger = Mockery::mock(Logger::class);
        $logger->shouldNotReceive('critical');
        $this->tester->mockService(Logger::class, $logger);
    }

    #[DataProvider('pageHandleProvider')]
    public function testEsiRendersRequestedBlockWithoutInitializingNavigation(array $pageHandles): void
    {
        $objectManager = $this->tester->getObjectManager();
        $client = Mockery::mock(Client::class);
        $client->shouldNotReceive('request');
        $this->tester->mockService(Client::class, $client);

        $request = $objectManager->get(Http::class);
        $request->setRouteName('magento_pagecache');
        $request->setControllerName('block');
        $request->setActionName('esi');
        $request->setParams([
            'blocks' => $objectManager->get(Json::class)->serialize(['catalog.topnav']),
            'handles' => $objectManager->get(Base64Json::class)->serialize(['default', ...$pageHandles]),
        ]);
        $controller = $objectManager->create(Esi::class);
        $controller->execute();

        $this->assertStringContainsString('<nav class="navigation"', $controller->getResponse()->getBody());
        $layout = $objectManager->get(ViewInterface::class)->getLayout();
        $this->assertTrue($layout->getBlock('catalog.leftnav') === false, 'Category navigation must not be generated.');
        $this->assertTrue($layout->getBlock('catalogsearch.leftnav') === false, 'Search navigation must not be generated.');
    }

    #[DataProvider('urlStrategyProvider')]
    public function testSearchPageStillLoadsFiltersWhenQueryHasNotBeenAppliedYet(string $urlStrategy): void
    {
        $objectManager = $this->tester->getObjectManager();
        $this->tester->mockConfig('tweakwise/layered/url_strategy', $urlStrategy);

        $request = $objectManager->get(Http::class);
        $request->setRouteName('catalogsearch');
        $request->setControllerName('result');
        $request->setActionName('index');
        $request->setQueryValue('q', 'chair');

        $client = Mockery::mock(Client::class);
        $client->shouldReceive('request')
            ->once()
            ->with(Mockery::on(
                static fn (ProductSearchRequest $searchRequest): bool => $searchRequest->getParameter('tn_q') === 'chair',
            ))
            ->andReturnUsing(
                static fn (ProductSearchRequest $searchRequest): ProductNavigationResponse => $objectManager->create(
                    ProductNavigationResponse::class,
                    ['request' => $searchRequest, 'data' => ['items' => [], 'facets' => []]],
                ),
            );
        $this->tester->mockService(Client::class, $client);

        $view = $objectManager->get(ViewInterface::class);
        $view->loadLayout(['default', 'catalogsearch_result_index'], true, true, false);

        $this->assertInstanceOf(Navigation::class, $view->getLayout()->getBlock('catalogsearch.leftnav'));
    }

    public static function pageHandleProvider(): array
    {
        return [
            'search page without query' => [['catalogsearch_result_index']],
            'category page without category ID' => [['catalog_category_view', 'catalog_category_view_type_layered']],
        ];
    }

    public static function urlStrategyProvider(): array
    {
        return [
            'query parameters' => [UrlStrategy::STRATEGY_QUERY_PARAM],
            'path slugs' => [UrlStrategy::STRATEGY_PATH_SLUGS],
        ];
    }
}
