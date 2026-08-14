<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model;

use Emico\CodeCept\Test\Unit;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;
use Mockery;
use Mockery\MockInterface;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\NavigationContext\CurrentContext;
use Tweakwise\Magento2Tweakwise\Model\FilterFormInputProvider\FilterFormInputProviderInterface;
use Tweakwise\Magento2Tweakwise\Model\FilterFormInputProvider\HashInputProvider;
use Tweakwise\Magento2Tweakwise\Model\NavigationConfig;
use Tweakwise\Magento2Tweakwise\Model\PersonalMerchandisingConfig;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\Strategy\QueryParameterStrategy;
use Tweakwise\Test\Support\UnitTester;

class NavigationConfigTest extends Unit
{
    protected UnitTester $tester;

    private PersonalMerchandisingConfig&MockInterface $config;

    private UrlInterface&MockInterface $url;

    private CurrentContext&MockInterface $currentContext;

    private ProductMetadataInterface&MockInterface $productMetadata;

    private FilterFormInputProviderInterface&MockInterface $filterFormInputProvider;

    private Json&MockInterface $jsonSerializer;

    private Http&MockInterface $request;

    private HashInputProvider&MockInterface $hashInputProvider;

    public function _before(): void
    {
        $this->config = Mockery::mock(PersonalMerchandisingConfig::class);
        $this->url = Mockery::mock(UrlInterface::class);
        $this->currentContext = Mockery::mock(CurrentContext::class);
        $this->productMetadata = Mockery::mock(ProductMetadataInterface::class);
        $this->filterFormInputProvider = Mockery::mock(FilterFormInputProviderInterface::class);
        $this->jsonSerializer = Mockery::mock(Json::class);
        $this->request = Mockery::mock(Http::class);
        $this->hashInputProvider = Mockery::mock(HashInputProvider::class);
    }

    public function _after(): void
    {
        Mockery::close();
    }

    public function testJsFormConfigIncludesCountEndpoint(): void
    {
        $this->config->shouldReceive('isFormFilters')->andReturn(true);
        $this->config->shouldReceive('isAjaxFilters')->andReturn(true);
        $this->config->shouldReceive('isSeoEnabled')->andReturn(false);
        $this->config->shouldReceive('getUrlStrategy')->andReturn(QueryParameterStrategy::class);
        $this->config->shouldReceive('isAnalyticsEnabled')->andReturn(true);
        $this->config->shouldReceive('isPersonalMerchandisingActive')->andReturn(false);

        $this->currentContext->shouldReceive('getTweakwiseRequestId')->andReturn('request-123');
        $this->url->shouldReceive('getUrl')->andReturnUsing(static function (string $route): string {
            return match ($route) {
                'tweakwise/ajax/navigation' => 'https://example.test/tweakwise/ajax/navigation',
                'tweakwise/ajax/productcount' => 'https://example.test/tweakwise/ajax/productcount',
                'tweakwise/ajax/analytics' => 'https://example.test/tweakwise/ajax/analytics',
                default => 'https://example.test/' . $route,
            };
        });
        $this->jsonSerializer->shouldReceive('serialize')->andReturnUsing(
            static fn(array $data): string => json_encode($data, JSON_THROW_ON_ERROR)
        );

        $subject = new NavigationConfig(
            $this->config,
            $this->url,
            $this->currentContext,
            $this->productMetadata,
            $this->filterFormInputProvider,
            $this->jsonSerializer,
            $this->request,
            $this->hashInputProvider,
        );

        $decoded = json_decode($subject->getJsFormConfig(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue($decoded['tweakwiseNavigationForm']['formFilters']);
        $this->assertTrue($decoded['tweakwiseNavigationForm']['ajaxFilters']);
        $this->assertSame('https://example.test/tweakwise/ajax/productcount', $decoded['tweakwiseNavigationForm']['countEndpoint']);
        $this->assertSame('request-123', $decoded['tweakwiseNavigationForm']['twRequestId']);
        $this->assertSame('queryparameter', $decoded['tweakwiseNavigationForm']['urlStrategy']);
    }
}
