<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Assistant;

use Emico\CodeCept\Test\Unit;
use Magento\Framework\App\Request\Http as MagentoHttpRequest;
use Magento\Framework\DataObject;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Mockery;
use Tweakwise\Magento2Tweakwise\Model\Assistant\PageContext;
use Tweakwise\Magento2Tweakwise\Model\Assistant\PageContextProvider;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\NavigationContext\CurrentContext;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\UrlInterface as LayerUrlInterface;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\ProductNavigationRequest;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\ProductSearchRequest;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2Tweakwise\Model\FilterFormInputProvider\HashInputProvider;

class PageContextProviderTest extends Unit
{
    public function testSignedContextRoundTrips(): void
    {
        $provider = $this->createProvider();
        $context = new PageContext(PageContext::TYPE_CATEGORY, 12, 'https://shop.test/women.html');
        $data = $context->getSignedData() + ['hash' => $provider->getHash($context)];

        $restored = $provider->fromSignedData($data, 'shoes');

        $this->assertNotNull($restored);
        $this->assertSame(12, $restored->getCategoryId());
        $this->assertSame('https://shop.test/women.html', $restored->getBaseUrl());
        $this->assertSame('shoes', $restored->getSearchTerm());
        $this->assertFalse($restored->isSearch());
    }

    /**
     * @dataProvider tamperedContexts
     */
    public function testTamperedContextIsRejected(array $changes): void
    {
        $provider = $this->createProvider();
        $context = new PageContext(PageContext::TYPE_CATEGORY, 12, 'https://shop.test/women.html');
        $data = array_merge($context->getSignedData() + ['hash' => $provider->getHash($context)], $changes);

        $this->assertNull($provider->fromSignedData($data));
    }

    public static function tamperedContexts(): array
    {
        return [
            'category' => [['categoryId' => 13]],
            'base url' => [['baseUrl' => 'https://evil.test/']],
            'type' => [['type' => PageContext::TYPE_SEARCH]],
            'hash' => [['hash' => 'invalid']],
            'missing hash' => [['hash' => null]],
            'unknown type' => [['type' => 'cms']],
        ];
    }

    public function testSearchPageUsesTheRootCategoryAndSearchTerm(): void
    {
        $request = Mockery::mock(ProductSearchRequest::class);
        $request->shouldReceive('getParameter')->with('tn_q')->andReturn('shoes');
        $request->shouldReceive('getParameters')->andReturn(
            ['tn_q' => 'shoes', 'tn_fk_kleur' => 'Rood|Zwart', 'tn_cid' => '2-12', 'tn_fk_prijs' => '5-50']
        );

        $provider = $this->createProvider($request);
        $context = $provider->getCurrentPageContext();

        $this->assertTrue($context->isSearch());
        $this->assertSame(2, $context->getCategoryId());
        $this->assertSame('https://shop.test/catalogsearch/result/', $context->getBaseUrl());
        $this->assertSame('shoes', $context->getSearchTerm());
        $this->assertSame(
            [['attribute' => 'kleur', 'value' => 'Rood|Zwart'], ['attribute' => 'prijs', 'value' => '5-50']],
            $provider->getCurrentSelectedFacets()
        );
    }

    public function testCategoryPageUsesTheCurrentCategoryAndOriginalUrl(): void
    {
        $request = Mockery::mock(ProductNavigationRequest::class);
        $request->shouldReceive('getParameter')->with('tn_q')->andReturn(null);

        $context = $this->createProvider($request)->getCurrentPageContext();

        $this->assertFalse($context->isSearch());
        $this->assertSame(12, $context->getCategoryId());
        $this->assertSame('https://shop.test/women.html', $context->getBaseUrl());
        $this->assertNull($context->getSearchTerm());
    }

    private function createProvider(?ProductNavigationRequest $navigationRequest = null): PageContextProvider
    {
        $config = Mockery::mock(Config::class);
        $config->shouldReceive('getSalt')->andReturn('salt');
        $encryptor = Mockery::mock(Encryptor::class);
        $encryptor->shouldReceive('hash')->andReturnUsing(static fn (string $data): string => hash('sha256', $data));
        $hashInputProvider = new HashInputProvider(
            Mockery::mock(MagentoHttpRequest::class),
            $config,
            $encryptor,
            new Json()
        );

        $currentContext = Mockery::mock(CurrentContext::class);
        if ($navigationRequest !== null) {
            $currentContext->shouldReceive('getRequest')->andReturn($navigationRequest);
        }

        $category = new DataObject(['id' => 12]);
        $registry = Mockery::mock(Registry::class);
        $registry->shouldReceive('registry')->with('current_category')->andReturn($category);

        $store = Mockery::mock(Store::class);
        $store->shouldReceive('getRootCategoryId')->andReturn(2);
        $storeManager = Mockery::mock(StoreManagerInterface::class);
        $storeManager->shouldReceive('getStore')->andReturn($store);

        $url = Mockery::mock(UrlInterface::class);
        $url->shouldReceive('getUrl')->with('catalogsearch/result')->andReturn('https://shop.test/catalogsearch/result/');

        $strategy = Mockery::mock(LayerUrlInterface::class);
        $strategy->shouldReceive('getOriginalUrl')->andReturn('https://shop.test/women.html?kleur=Rood&p=2');
        $layerUrl = Mockery::mock(Url::class);
        $layerUrl->shouldReceive('getUrlStrategy')->andReturn($strategy);

        return new PageContextProvider(
            $currentContext,
            $registry,
            $storeManager,
            $url,
            $layerUrl,
            Mockery::mock(MagentoHttpRequest::class),
            $hashInputProvider
        );
    }
}
