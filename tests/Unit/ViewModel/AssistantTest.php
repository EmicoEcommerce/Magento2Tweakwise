<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\ViewModel;

use Emico\CodeCept\Test\Unit;
use Magento\Catalog\Model\Category;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\JsonHexTag;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Mockery;
use RuntimeException;
use Tweakwise\Magento2Tweakwise\Exception\ApiException;
use Tweakwise\Magento2Tweakwise\Model\Assistant\PageContext;
use Tweakwise\Magento2Tweakwise\Model\Assistant\PageContextProvider;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\NavigationContext;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\NavigationContext\CurrentContext;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\ProductNavigationRequest;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\ProductSearchRequest;
use Tweakwise\Magento2Tweakwise\Model\Client\Response\ProductNavigationResponse;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2Tweakwise\ViewModel\Assistant;
use Tweakwise\Magento2TweakwiseExport\Model\Helper;

class AssistantTest extends Unit
{
    private array $settings;

    // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    public function _before(): void
    {
        $this->settings = [
            'configEnabled' => true,
            'exceptionThrown' => false,
            'layeredEnabled' => true,
            'searchEnabled' => true,
            'search' => false,
            'anchor' => true,
            'displayMode' => Category::DM_PRODUCT,
            'assistant' => ['enabled' => 'true', 'reason' => []],
            'responseException' => null,
        ];
    }

    public function testAvailableOnCategoryPage(): void
    {
        $viewModel = $this->createViewModel();

        $this->assertTrue($viewModel->isAvailable());
        $this->assertNull($viewModel->getUnavailableReason());
    }

    public function testAvailableOnSearchPage(): void
    {
        $this->settings['search'] = true;
        $this->settings['layeredEnabled'] = false;

        $this->assertTrue($this->createViewModel()->isAvailable());
    }

    /**
     * @dataProvider unsupportedPages
     */
    public function testHiddenWhenTweakwiseDoesNotHandleThePage(array $settings): void
    {
        $this->settings = array_merge($this->settings, $settings);

        $this->assertFalse($this->createViewModel()->isAvailable());
    }

    public static function unsupportedPages(): array
    {
        return [
            'module setting disabled' => [['configEnabled' => false]],
            'tweakwise request failed' => [['exceptionThrown' => true]],
            'navigation disabled' => [['layeredEnabled' => false]],
            'search disabled' => [['search' => true, 'searchEnabled' => false]],
            'non anchor category' => [['anchor' => false]],
            'cms only category' => [['displayMode' => Category::DM_PAGE]],
            'disabled by template' => [['assistant' => ['enabled' => 'false', 'reason' => 'disabled-by-template']]],
            'no assistant node' => [['assistant' => null]],
            'api exception' => [['responseException' => new ApiException('failed')]],
            'no navigation context' => [['responseException' => new RuntimeException('no context')]],
        ];
    }

    /**
     * @dataProvider quotaReasons
     */
    public function testShownWithReasonWhenQuotaIsReached(string $reason): void
    {
        $this->settings['assistant'] = ['enabled' => 'false', 'reason' => $reason];
        $viewModel = $this->createViewModel();

        $this->assertTrue($viewModel->isAvailable());
        $this->assertSame($reason, $viewModel->getUnavailableReason());
    }

    public static function quotaReasons(): array
    {
        return [['quota-reached'], ['quota-unavailable']];
    }

    public function testJsConfigContainsSignedContext(): void
    {
        $config = json_decode($this->createViewModel()->getJsConfig(['search' => 'Search']), true);
        $options = $config['Tweakwise_Magento2Tweakwise/js/assistant'];

        $this->assertSame('https://shop.test/tweakwise/ajax/assistant/', $options['endpoint']);
        $this->assertSame('tw-assistant-default', $options['storageKey']);
        $this->assertSame(
            ['type' => 'category', 'categoryId' => 12, 'baseUrl' => 'https://shop.test/women.html', 'hash' => 'hash'],
            $options['context']
        );
        $this->assertSame([['attribute' => 'kleur', 'value' => 'Rood']], $options['selectedFacets']);
        $this->assertSame(['search' => 'Search'], $options['labels']);
    }

    private function createViewModel(): Assistant
    {
        $settings = $this->settings;
        $config = Mockery::mock(Config::class);
        $config->shouldReceive('isAssistantEnabled')->andReturn($settings['configEnabled']);
        $config->shouldReceive('getTweakwiseExceptionThrown')->andReturn($settings['exceptionThrown']);
        $config->shouldReceive('isLayeredEnabled')->andReturn($settings['layeredEnabled']);
        $config->shouldReceive('isSearchEnabled')->andReturn($settings['searchEnabled']);

        $request = Mockery::mock($settings['search'] ? ProductSearchRequest::class : ProductNavigationRequest::class);
        $navigationContext = Mockery::mock(NavigationContext::class);
        $navigationContext->shouldReceive('getRequest')->andReturn($request);
        if ($settings['responseException']) {
            $navigationContext->shouldReceive('getResponse')->andThrow($settings['responseException']);
        } else {
            $data = $settings['assistant'] !== null ? ['assistant' => $settings['assistant']] : [];
            $navigationContext->shouldReceive('getResponse')->andReturn(
                new ProductNavigationResponse(Mockery::mock(Helper::class), $request, $data)
            );
        }
        $currentContext = Mockery::mock(CurrentContext::class);
        $currentContext->shouldReceive('getContext')->andReturn($navigationContext);

        $category = Mockery::mock(Category::class);
        $category->shouldReceive('getIsAnchor')->andReturn($settings['anchor']);
        $category->shouldReceive('getDisplayMode')->andReturn($settings['displayMode']);
        $registry = Mockery::mock(Registry::class);
        $registry->shouldReceive('registry')->with('current_category')->andReturn($category);

        $pageContext = new PageContext(PageContext::TYPE_CATEGORY, 12, 'https://shop.test/women.html');
        $pageContextProvider = Mockery::mock(PageContextProvider::class);
        $pageContextProvider->shouldReceive('getCurrentPageContext')->andReturn($pageContext);
        $pageContextProvider->shouldReceive('getHash')->with($pageContext)->andReturn('hash');
        $pageContextProvider->shouldReceive('getCurrentSelectedFacets')->andReturn(
            [['attribute' => 'kleur', 'value' => 'Rood']]
        );

        $store = Mockery::mock(Store::class);
        $store->shouldReceive('getCode')->andReturn('default');
        $storeManager = Mockery::mock(StoreManagerInterface::class);
        $storeManager->shouldReceive('getStore')->andReturn($store);
        $url = Mockery::mock(UrlInterface::class);
        $url->shouldReceive('getUrl')->with('tweakwise/ajax/assistant')->andReturn('https://shop.test/tweakwise/ajax/assistant/');

        return new Assistant(
            $config,
            $currentContext,
            $pageContextProvider,
            $registry,
            $storeManager,
            $url,
            new JsonHexTag()
        );
    }
}
