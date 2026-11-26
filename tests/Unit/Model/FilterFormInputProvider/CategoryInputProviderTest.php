<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\FilterFormInputProvider;

use Emico\CodeCept\Test\Unit;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterfaceFactory;
use Magento\Catalog\Model\Category;
use Magento\Framework\App\Request\Http as MagentoHttpRequest;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mockery;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\UrlInterface as UrlStrategy;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2Tweakwise\Model\FilterFormInputProvider\CategoryInputProvider;
use Tweakwise\Magento2Tweakwise\Model\FilterFormInputProvider\HashInputProvider;
use Tweakwise\Magento2Tweakwise\Model\FilterFormInputProvider\ToolbarInputProvider;

class CategoryInputProviderTest extends Unit
{
    // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    public function _after(): void
    {
        Mockery::close();
    }

    /**
     * Filtering after an AI assistant search within the category keeps searching within the category
     */
    public function testSearchTermOfTheAssistantIsKept(): void
    {
        $input = $this->createProvider(true, true, ' jacket ')->getFilterFormInput();

        $this->assertSame('jacket', $input['q']);
        $this->assertSame('hash', $input['__tw_hash']);
    }

    public function testSearchTermIsKeptWithoutAjaxFilters(): void
    {
        $this->assertSame(['q' => 'jacket'], $this->createProvider(false, true, 'jacket')->getFilterFormInput());
    }

    public function testSearchTermIsIgnoredWhenTheAssistantIsDisabled(): void
    {
        $this->assertArrayNotHasKey('q', $this->createProvider(true, false, 'jacket')->getFilterFormInput());
        $this->assertSame([], $this->createProvider(false, false, 'jacket')->getFilterFormInput());
    }

    public function testEmptySearchTermIsNotAdded(): void
    {
        $this->assertArrayNotHasKey('q', $this->createProvider(true, true, ' ')->getFilterFormInput());
    }

    private function createProvider(bool $ajaxFilters, bool $assistant, ?string $search): CategoryInputProvider
    {
        $config = Mockery::mock(Config::class);
        $config->shouldReceive('isAjaxFilters')->andReturn($ajaxFilters);
        $config->shouldReceive('isAssistantEnabled')->andReturn($assistant);

        $request = Mockery::mock(MagentoHttpRequest::class);
        $request->shouldReceive('getQuery')->with('q')->andReturn($search);

        $urlStrategy = Mockery::mock(UrlStrategy::class);
        $urlStrategy->shouldReceive('getOriginalUrl')->andReturn('catalog/category/view/id/23?q=jacket');
        $layerUrl = Mockery::mock(Url::class);
        $layerUrl->shouldReceive('getUrlStrategy')->andReturn($urlStrategy);

        $category = Mockery::mock(Category::class);
        $category->shouldReceive('getId')->andReturn(23);
        $registry = Mockery::mock(Registry::class);
        $registry->shouldReceive('registry')->with('current_category')->andReturn($category);

        $hashInputProvider = Mockery::mock(HashInputProvider::class);
        $hashInputProvider->shouldReceive('getHash')->andReturn('hash');
        $toolbarInputProvider = Mockery::mock(ToolbarInputProvider::class);
        $toolbarInputProvider->shouldReceive('getFilterFormInput')->andReturn([]);

        return new CategoryInputProvider(
            Mockery::mock(UrlInterface::class),
            $registry,
            $config,
            Mockery::mock(StoreManagerInterface::class),
            Mockery::mock(CategoryRepositoryInterface::class),
            Mockery::mock(CategoryInterfaceFactory::class),
            $toolbarInputProvider,
            $hashInputProvider,
            $layerUrl,
            $request
        );
    }
}
