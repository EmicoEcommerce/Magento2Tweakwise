<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Catalog\Layer\Url\Strategy;

use Emico\CodeCept\Test\Unit;
use Laminas\Stdlib\Parameters;
use Magento\Catalog\Model\Product\ProductList\ToolbarMemorizer;
use Magento\Search\Helper\Data;
use Magento\Framework\App\Request\Http as MagentoHttpRequest;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use Mockery;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\Strategy\QueryParameterStrategy;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\StrategyHelper;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\UrlModel;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\ProductNavigationRequest;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\ProductSearchRequest;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2TweakwiseExport\Model\Helper;

/**
 * The AI assistant searches within the current category, like the Tweakwise demoshop. A search term on a category page
 * is sent to navigation-search with the category id instead of redirecting to the store wide search results page.
 */
class QueryParameterStrategyCategorySearchTest extends Unit
{
    public function testSearchTermOnCategoryPageSearchesWithinTheCategory(): void
    {
        $navigationRequest = $this->createNavigationRequest(ProductNavigationRequest::class);
        $navigationRequest->addCategoryFilter(12);

        $this->createStrategy(true)->apply($this->createHttpRequest(['q' => 'red', 'kleur' => 'Rood']), $navigationRequest);

        $this->assertSame('navigation-search', $navigationRequest->getPath());
        $this->assertSame('red', $navigationRequest->getParameter('tn_q'));
        $this->assertSame('1000112', $navigationRequest->getParameter('tn_cid'));
        $this->assertSame('Rood', $navigationRequest->getParameter('tn_fk_kleur'));
    }

    public function testGroupedCategorySearchUsesTheGroupedSearchEndpoint(): void
    {
        $navigationRequest = $this->createNavigationRequest(ProductNavigationRequest::class, true);

        $this->createStrategy(true)->apply($this->createHttpRequest(['q' => 'red']), $navigationRequest);

        $this->assertSame('navigation-search/grouped', $navigationRequest->getPath());
    }

    public function testSearchTermOnCategoryPageIsIgnoredWhenTheAssistantIsDisabled(): void
    {
        $navigationRequest = $this->createNavigationRequest(ProductNavigationRequest::class);

        $this->createStrategy(false)->apply($this->createHttpRequest(['q' => 'red']), $navigationRequest);

        $this->assertSame('navigation', $navigationRequest->getPath());
        $this->assertNull($navigationRequest->getParameter('tn_q'));
    }

    public function testCategoryPageWithoutSearchTermUsesNavigation(): void
    {
        $navigationRequest = $this->createNavigationRequest(ProductNavigationRequest::class);

        $this->createStrategy(true)->apply($this->createHttpRequest(['q' => '  ']), $navigationRequest);

        $this->assertSame('navigation', $navigationRequest->getPath());
        $this->assertNull($navigationRequest->getParameter('tn_q'));
    }

    public function testSearchPageStillUsesTheSearchTerm(): void
    {
        $navigationRequest = $this->createNavigationRequest(ProductSearchRequest::class);
        $navigationRequest->addCategoryFilter(2);

        $this->createStrategy(false)->apply($this->createHttpRequest(['q' => 'red']), $navigationRequest);

        $this->assertSame('navigation-search', $navigationRequest->getPath());
        $this->assertSame('red', $navigationRequest->getParameter('tn_q'));
    }

    /**
     * @param class-string<ProductNavigationRequest> $class
     */
    private function createNavigationRequest(string $class, bool $grouped = false): ProductNavigationRequest
    {
        $store = Mockery::mock(Store::class);
        $store->shouldReceive('getId')->andReturn(1);
        $storeManager = Mockery::mock(StoreManager::class);
        $storeManager->shouldReceive('getStore')->andReturn($store);

        $helper = Mockery::mock(Helper::class);
        $helper->shouldReceive('getTweakwiseId')->andReturnUsing(
            static fn (int $storeId, int $id): string => '1' . str_pad((string)$storeId, 4, '0', STR_PAD_LEFT) . $id
        );

        $config = Mockery::mock(Config::class);
        $config->shouldReceive('isGroupedProductsEnabled')->andReturn($grouped);
        $config->shouldReceive('isCategoryViewDefault')->andReturn(false);

        return new $class($helper, $storeManager, $config);
    }

    private function createHttpRequest(array $query): MagentoHttpRequest
    {
        $parameters = new Parameters($query);
        $request = Mockery::mock(MagentoHttpRequest::class);
        $request->shouldReceive('getQuery')->andReturnUsing(
            static fn (?string $name = null) => $name === null ? $parameters : $parameters->get($name)
        );

        return $request;
    }

    private function createStrategy(bool $assistantEnabled): QueryParameterStrategy
    {
        $config = Mockery::mock(Config::class);
        $config->shouldReceive('isAssistantEnabled')->andReturn($assistantEnabled);
        $config->shouldReceive('isPersonalMerchandisingActive')->andReturn(false);

        $searchConfig = Mockery::mock(Data::class);
        $searchConfig->shouldReceive('getMaxQueryLength')->andReturn(128);

        $toolbarMemorizer = Mockery::mock(ToolbarMemorizer::class);
        $toolbarMemorizer->shouldReceive('getOrder')->andReturn(null);

        return new QueryParameterStrategy(
            Mockery::mock(UrlModel::class),
            Mockery::mock(StrategyHelper::class),
            Mockery::mock(CookieManagerInterface::class),
            $config,
            Mockery::mock(Url::class),
            $searchConfig,
            Mockery::mock(SerializerInterface::class),
            $toolbarMemorizer
        );
    }
}
