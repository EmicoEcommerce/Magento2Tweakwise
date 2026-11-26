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

    /**
     * @param array $params
     * @return MagentoHttpRequest
     */
    private function createRequest(array $params): MagentoHttpRequest
    {
        $request = Mockery::mock(MagentoHttpRequest::class);
        $request->shouldReceive('getParam')->andReturnUsing(
            static fn (string $key) => $params[$key] ?? null
        );

        return $request;
    }

    private function createStrategy(): PathSlugStrategy
    {
        $magentoUrl = Mockery::mock(UrlModel::class);
        $magentoUrl->shouldReceive('getBaseUrl')->andReturn(self::BASE_URL);
        $magentoUrl->shouldReceive('getDirectUrl')->andReturnUsing(
            static function (string $path, array $params): string {
                $query = http_build_query($params['_query'] ?? []);

                return self::BASE_URL . $path . ($query !== '' ? '?' . $query : '');
            }
        );

        return new PathSlugStrategy(
            $magentoUrl,
            Mockery::mock(UrlFactory::class),
            Mockery::mock(Resolver::class),
            Mockery::mock(FilterSlugManager::class),
            Mockery::mock(QueryParameterStrategy::class),
            Mockery::mock(Config::class),
            Mockery::mock(CurrentContext::class),
            Mockery::mock(ScopeConfigInterface::class),
            Mockery::mock(StrategyHelper::class),
            [],
            []
        );
    }
}
