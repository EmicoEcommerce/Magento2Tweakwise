<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Catalog\Layer\Url\Strategy;

use Emico\CodeCept\Test\Unit;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http as MagentoHttpRequest;
use Mockery;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\NavigationContext\CurrentContext;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\Strategy\FilterSlugManager;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\Strategy\PathSlugStrategy;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\Strategy\QueryParameterStrategy;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\StrategyHelper;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\UrlModel;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\UrlFactory;
use Tweakwise\Magento2Tweakwise\Model\Config;

/**
 * The ajax filter form posts the AI assistant search term as "q". The response URL must keep it, because the
 * navigation form removes form parameters from the browser URL and relies on the response URL to restore them.
 * Without ajax the filter links are followed directly, so they must keep the search term as well.
 */
class PathSlugStrategyOriginalUrlTest extends Unit
{
    private const BASE_URL = 'https://shop.test/';

    public function testAjaxOriginalUrlKeepsTheSearchTerm(): void
    {
        $url = $this->createStrategy()->getOriginalUrl(
            $this->createRequest(['__tw_original_url' => 'jackets.html', 'q' => ' waterproof ', 'product_list_order' => 'name'])
        );

        $this->assertSame('jackets.html?product_list_order=name&q=waterproof', $url);
    }

    public function testAjaxOriginalUrlWithoutSearchTermIsUnchanged(): void
    {
        $url = $this->createStrategy()->getOriginalUrl(
            $this->createRequest(['__tw_original_url' => 'jackets.html', 'q' => ' ', 'p' => '2'])
        );

        $this->assertSame('jackets.html?p=2', $url);
    }

    public function testAjaxOriginalUrlIgnoresTheSearchTermWhenTheAssistantIsDisabled(): void
    {
        $url = $this->createStrategy(false)->getOriginalUrl(
            $this->createRequest(['__tw_original_url' => 'jackets.html', 'q' => 'waterproof'])
        );

        $this->assertSame('jackets.html', $url);
    }

    public function testFilterLinkKeepsTheSearchTermWithoutAjax(): void
    {
        $url = $this->createStrategy()->buildFilterUrl(
            $this->createRequest(['q' => ' water & proof '], '/men/jackets/'),
            ['ae-color/gray']
        );

        $this->assertSame('/men/jackets/ae-color/gray/?q=water+%26+proof', $url);
    }

    public function testFilterLinkFromAFilteredPageKeepsTheSearchTermWithoutAjax(): void
    {
        $url = $this->createStrategy(true, ['ae-color/gray'])->buildFilterUrl(
            $this->createRequest(['q' => 'jacket', 'filter_path' => 'ae-color/gray'], '/men/jackets/ae-color/gray/'),
            ['ae-color/gray', 'ae-color/red']
        );

        $this->assertSame('/men/jackets/ae-color/gray/ae-color/red/?q=jacket', $url);
    }

    public function testClearLinkKeepsTheSearchTermWithoutAjax(): void
    {
        $url = $this->createStrategy(true, ['ae-color/gray'])->getClearUrl(
            $this->createRequest(['q' => 'jacket', 'filter_path' => 'ae-color/gray'], '/men/jackets/ae-color/gray/'),
            []
        );

        $this->assertSame('/men/jackets/?q=jacket', $url);
    }

    public function testAjaxFilterLinkContainsTheSearchTermOnce(): void
    {
        $url = $this->createStrategy()->buildFilterUrl(
            $this->createRequest(['__tw_original_url' => 'men/jackets/', 'q' => 'jacket']),
            ['ae-color/gray']
        );

        $this->assertSame('men/jackets/ae-color/gray/?q=jacket', $url);
    }

    public function testFilterLinkIgnoresTheSearchTermWhenTheAssistantIsDisabled(): void
    {
        $url = $this->createStrategy(false)->buildFilterUrl(
            $this->createRequest(['q' => 'jacket'], '/men/jackets/'),
            ['ae-color/gray']
        );

        $this->assertSame('/men/jackets/ae-color/gray/', $url);
    }

    /**
     * @param array $params
     * @param string $pathInfo
     * @return MagentoHttpRequest
     */
    private function createRequest(array $params, string $pathInfo = ''): MagentoHttpRequest
    {
        $request = Mockery::mock(MagentoHttpRequest::class);
        $request->shouldReceive('getParam')->andReturnUsing(
            static fn (string $key) => $params[$key] ?? null
        );
        $request->shouldReceive('getOriginalPathInfo')->andReturn($pathInfo);

        return $request;
    }

    /**
     * Filters are represented by their slug path ("url-key/slug") to keep the layer out of these tests
     *
     * @param bool $assistantEnabled
     * @param array $activeFilters
     * @return PathSlugStrategy
     */
    private function createStrategy(bool $assistantEnabled = true, array $activeFilters = []): PathSlugStrategy
    {
        $config = Mockery::mock(Config::class);
        $config->shouldReceive('isAssistantEnabled')->andReturn($assistantEnabled);

        $magentoUrl = Mockery::mock(UrlModel::class);
        $magentoUrl->shouldReceive('getBaseUrl')->andReturn(self::BASE_URL);
        $magentoUrl->shouldReceive('getDirectUrl')->andReturnUsing(
            static function (string $path, array $params): string {
                $query = http_build_query($params['_query'] ?? []);

                return self::BASE_URL . $path . ($query !== '' ? '?' . $query : '');
            }
        );

        return new class (
            $magentoUrl,
            Mockery::mock(UrlFactory::class),
            Mockery::mock(Resolver::class),
            Mockery::mock(FilterSlugManager::class),
            Mockery::mock(QueryParameterStrategy::class),
            $config,
            Mockery::mock(CurrentContext::class),
            Mockery::mock(ScopeConfigInterface::class),
            Mockery::mock(StrategyHelper::class),
            [],
            [],
            $activeFilters
        ) extends PathSlugStrategy {
            /** @var array */
            private array $testActiveFilters;

            /**
             * @param mixed ...$arguments
             */
            public function __construct(...$arguments)
            {
                $this->testActiveFilters = array_pop($arguments);
                parent::__construct(...$arguments);
            }

            /**
             * @return array
             */
            protected function getActiveFilters(): array
            {
                return $this->testActiveFilters;
            }

            /**
             * @param array $filters
             * @return string
             */
            protected function buildFilterSlugPath(array $filters): string
            {
                return $filters === [] ? '' : '/' . implode('/', $filters) . '/';
            }
        };
    }
}
