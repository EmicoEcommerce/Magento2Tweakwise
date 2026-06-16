<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model;

use Emico\CodeCept\Test\Unit;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\MockObject\MockObject;
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

    private PersonalMerchandisingConfig&MockObject $config;

    private UrlInterface&MockObject $url;

    private CurrentContext&MockObject $currentContext;

    private ProductMetadataInterface&MockObject $productMetadata;

    private FilterFormInputProviderInterface&MockObject $filterFormInputProvider;

    private Json&MockObject $jsonSerializer;

    private Http&MockObject $request;

    private HashInputProvider&MockObject $hashInputProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = $this->createMock(PersonalMerchandisingConfig::class);
        $this->url = $this->createMock(UrlInterface::class);
        $this->currentContext = $this->createMock(CurrentContext::class);
        $this->productMetadata = $this->createMock(ProductMetadataInterface::class);
        $this->filterFormInputProvider = $this->createMock(FilterFormInputProviderInterface::class);
        $this->jsonSerializer = $this->createMock(Json::class);
        $this->request = $this->createMock(Http::class);
        $this->hashInputProvider = $this->createMock(HashInputProvider::class);
    }

    public function testJsFormConfigIncludesCountEndpoint(): void
    {
        $this->config->method('isFormFilters')->willReturn(true);
        $this->config->method('isAjaxFilters')->willReturn(true);
        $this->config->method('isSeoEnabled')->willReturn(false);
        $this->config->method('getUrlStrategy')->willReturn(QueryParameterStrategy::class);
        $this->config->method('isAnalyticsEnabled')->willReturn(true);
        $this->config->method('isPersonalMerchandisingActive')->willReturn(false);

        $this->currentContext->method('getTweakwiseRequestId')->willReturn('request-123');
        $this->url->method('getUrl')->willReturnCallback(static function (string $route): string {
            return match ($route) {
                'tweakwise/ajax/navigation' => 'https://example.test/tweakwise/ajax/navigation',
                'tweakwise/ajax/productcount' => 'https://example.test/tweakwise/ajax/productcount',
                'tweakwise/ajax/analytics' => 'https://example.test/tweakwise/ajax/analytics',
                default => 'https://example.test/' . $route,
            };
        });
        $this->jsonSerializer->method('serialize')->willReturnCallback(
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
