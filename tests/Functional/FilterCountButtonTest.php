<?php

declare(strict_types=1);

namespace Tweakwise\Test\Functional;

use Emico\CodeCept\Models\Fixtures\CategoryFixture;
use Emico\CodeCept\Models\Fixtures\ProductFixture;
use Emico\CodeCept\Test\Unit;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Layer\Resolver as LayerResolver;
use Magento\Catalog\Model\Layer\Search as SearchLayer;
use Magento\Framework\Exception\NoSuchEntityException;
use Mockery;
use Tweakwise\Magento2Tweakwise\Model\Client;
use Tweakwise\Magento2Tweakwise\Model\Client\Response\ProductNavigationResponse;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Filter;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\FilterList\Tweakwise as TweakwiseFilterList;
use Tweakwise\Magento2Tweakwise\Model\Config\TemplateFinder;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\FacetType;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\FacetType\SettingsType;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\ItemType;
use Tweakwise\Magento2TweakwiseExport\Model\ProductAttributes;
use Tweakwise\Test\Support\FunctionalTester;

class FilterCountButtonTest extends Unit
{
    protected FunctionalTester $tester;

    private CategoryFixture $category;

    private ProductFixture $product;

    // phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
    /**
     * @return void
     */
    public function _before(): void
    {
        /** @var CategoryFixture $category */
        $category = $this->tester->getObjectManager()->create(
            CategoryFixture::class,
            ['name' => 'Filter Count Button Category']
        );
        $category->addAttribute('is_anchor', 1);
        $this->category = $category;
        $this->tester->createFixture($this->category);

        /** @var ProductFixture $product */
        $product = $this->tester->getObjectManager()->create(
            ProductFixture::class,
            ['sku' => 'tweakwise-filter-count-button-test']
        );
        $product->assignToCategory($this->category);
        $this->product = $product;
        $this->tester->createFixture($this->product);

        $this->tester->mockConfig('tweakwise/general/enabled', '1');
        $this->tester->mockConfig('tweakwise/general/analytics_enabled', '0');
        $this->tester->mockConfig('tweakwise/layered/enabled', '1');
        $this->tester->mockConfig('tweakwise/layered/default_link_renderer', '0');
        $this->tester->mockConfig('tweakwise/search/enabled', '1');
        $this->tester->mockConfig('tweakwise/autocomplete/enabled', '0');
        $this->tester->mockConfig('tweakwise/recommendations/featured_enabled', '0');
        $this->tester->mockConfig('tweakwise/recommendations/upsell_enabled', '0');
        $this->tester->mockConfig('tweakwise/recommendations/crosssell_enabled', '0');
        $this->tester->mockConfig('tweakwise/recommendations/shoppingcart_crosssell_enabled', '0');

        $productAttributes = Mockery::mock(ProductAttributes::class);
        $productAttributes->shouldReceive('getAttributesToExport')->andReturn([]);
        $this->tester->mockService(ProductAttributes::class, $productAttributes);

        $templateFinder = Mockery::mock(TemplateFinder::class);
        $templateFinder->shouldReceive('forCategory')->andReturn(null);
        $templateFinder->shouldReceive('forProduct')->andReturn(null);
        $this->tester->mockService(TemplateFinder::class, $templateFinder);

        $this->mockClientWithCheckboxFacet();
        $this->mockFilterListService();
    }

    /**
     * @return void
     */
    public function testFilterButtonRendersCountLabelWhenFormFiltersEnabled(): void
    {
        $this->tester->mockConfig('tweakwise/layered/form_filters', '1');
        $this->tester->clearCache();

        $this->tester->amOnPage('/catalog/category/view/id/' . $this->category->getId());
        $this->assertNoTweakwiseFallback();
        $this->assertProductAndFiltersPresent();
        $this->tester->seeInSource('"tweakwiseNavigationForm":{"formFilters":true');
        $this->tester->seeElement('.js-btn-filter');

        $countLabel = $this->tester->grabAttributeFrom('.js-btn-filter', 'data-count-label');
        $this->assertNotNull($countLabel);
        $this->assertNotSame('', trim($countLabel));
    }

    /**
     * @return void
     */
    public function testFilterButtonNotRenderedWhenFormFiltersDisabled(): void
    {
        $this->tester->mockConfig('tweakwise/layered/form_filters', '0');
        $this->tester->clearCache();

        $this->tester->amOnPage('/catalog/category/view/id/' . $this->category->getId());
        $this->assertNoTweakwiseFallback();
        $this->assertProductAndFiltersPresent();
        $this->tester->seeInSource('"tweakwiseNavigationForm":{"formFilters":false');
        $this->tester->dontSeeElement('.js-btn-filter');
    }

    /**
     * Fail loudly instead of silently passing/failing via Magento's native Elasticsearch
     * fallback. If any (unmocked) Tweakwise\Model\Client call fails elsewhere on the page
     * (autosuggest, recommendations, analytics), Config::setTweakwiseExceptionThrown() is
     * set and ItemCollectionProvider falls back to native search results instead of using
     * our mocked facets/items - which behaves differently depending on real DB/index state.
     *
     * @return void
     */
    private function assertNoTweakwiseFallback(): void
    {
        /** @var Config $config */
        $config = $this->tester->getObjectManager()->get(Config::class);
        $this->assertFalse(
            $config->getTweakwiseExceptionTrown(),
            'Tweakwise client threw an exception somewhere on the page; the test is '
            . 'exercising Magento\'s native Elasticsearch fallback instead of the mocked '
            . 'Tweakwise response, which is DB/index-state dependent.'
        );
    }

    /**
     * Pinpoints whether canShowBlock()'s two conditions (non-empty product collection,
     * non-empty filter list) are actually met on the rendered page, to distinguish a
     * missing/misassigned fixture product (empty product collection) from a facet/filter
     * building problem (empty filter list) - both hide the layered nav block, including
     * the filter button, but for entirely different reasons.
     *
     * @return void
     */
    private function assertProductAndFiltersPresent(): void
    {
        $objectManager = $this->tester->getObjectManager();

        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $objectManager->get(ProductRepositoryInterface::class);
        try {
            $productRepository->getById($this->product->getId());
        } catch (NoSuchEntityException $e) {
            $this->fail(sprintf(
                'Fixture product id %s does not exist in the database: %s',
                $this->product->getId(),
                $e->getMessage()
            ));
        }

        /** @var LayerResolver $layerResolver */
        $layerResolver = $objectManager->get(LayerResolver::class);
        $layer = $layerResolver->get();

        $productCollectionSize = $layer->getProductCollection()->getSize();
        $this->assertGreaterThan(
            0,
            $productCollectionSize,
            'Layer product collection is empty; fixture product '
            . $this->product->getId() . ' was not returned by ItemCollectionProvider '
            . '(likely a store/website assignment or entity_id mismatch issue in an '
            . 'empty database).'
        );

        // Plain Magento\Catalog\Model\Layer\FilterList can't be instantiated directly -
        // its FilterableAttributeListInterface argument is only bound for the
        // "searchFilterList"/"categoryFilterList" virtual types (Magento core
        // module-catalog/etc/di.xml). Resolve the same virtual type as the current layer.
        $filterListType = $layer instanceof SearchLayer ? 'searchFilterList' : 'categoryFilterList';
        $filterCount = count($objectManager->get($filterListType)->getFilters($layer));
        $this->assertGreaterThan(
            0,
            $filterCount,
            'Layer filter list is empty; the mocked facet did not turn into a filter '
            . '(hasFilters is false), so the layered nav block is hidden regardless of '
            . 'the product collection.'
        );
    }

    /**
     * Build a mocked Client that returns a ProductNavigationResponse containing a single
     * checkbox facet with one attribute item, so the filter button renders regardless
     * of the database state (no products/attributes required).
     *
     * @return void
     */
    private function mockClientWithCheckboxFacet(): void
    {
        $facetData = [
            'facetsettings' => [
                'source'       => SettingsType::SOURCE_FEED,
                'title'        => 'Color',
                'attributename' => 'tw_test_color',
                'urlkey'       => 'color',
                'selectiontype' => SettingsType::SELECTION_TYPE_CHECKBOX,
                'isnrofresultsvisible' => 'true',
                'ismultiselect' => 'true',
                'iscollapsible' => 'false',
                'iscollapsed'  => 'false',
                'isinfivisible' => 'false',
                'isvisible'    => 'true',
                'nrofshownattributes' => 10,
            ],
            'attributes' => [
                [
                    'title'        => 'Red',
                    'url'          => 'https://tweakwise.test/default/color/red/',
                    'link'         => '',
                    'nrofresults'  => 5,
                    'isselected'   => 'true',
                    'attributeid'  => '100001',
                ],
                [
                    'title'        => 'Blue',
                    'url'          => 'https://tweakwise.test/default/color/blue/',
                    'link'         => '',
                    'nrofresults'  => 3,
                    'isselected'   => 'false',
                    'attributeid'  => '100002',
                ],
            ],
        ];

        /** @var FacetType $facet */
        $facet = $this->tester->getObjectManager()->create(FacetType::class, ['data' => $facetData]);

        /** @var ItemType $item */
        $item = $this->tester->getObjectManager()->create(
            ItemType::class,
            [
                'data' => [
                    ItemType::ID    => $this->product->getId(),
                    ItemType::TYPE  => 'product',
                    ItemType::TITLE => $this->product->getName(),
                ],
            ]
        );

        // Product collection must be non-empty, otherwise Magento's core
        // Navigation::canShowBlock() hides the whole layered nav block (including the
        // filter button) regardless of the facets present in the response.
        /** @var ProductNavigationResponse $response */
        $response = $this->tester->getObjectManager()->create(
            ProductNavigationResponse::class,
            [
                'data' => [
                    'facets'     => [$facet],
                    'items'      => [$item],
                    'properties' => [
                        'nrofitems'          => 1,
                        'nrofpages'          => 1,
                        'currentpage'        => 1,
                        'nrofitemsperpage'   => 16,
                        'selectedcategoryid' => null,
                    ],
                ],
            ]
        );

        $client = Mockery::mock(Client::class);
        $client->shouldReceive('request')->andReturn($response);

        $this->tester->mockService(Client::class, $client);
    }

    /**
     * Avoid DB-dependent facet-to-attribute mapping queries in functional CI by
     * mocking the filter list builder directly.
     *
     * @return void
     */
    private function mockFilterListService(): void
    {
        $service = Mockery::mock(TweakwiseFilterList::class);
        $service->shouldReceive('getFilters')->andReturnUsing(function ($layer): array {
            $facetData = [
                'facetsettings' => [
                    'source' => SettingsType::SOURCE_FEED,
                    'title' => 'Color',
                    'attributename' => 'tw_test_color',
                    'urlkey' => 'color',
                    'selectiontype' => SettingsType::SELECTION_TYPE_CHECKBOX,
                    'isnrofresultsvisible' => 'true',
                    'ismultiselect' => 'true',
                    'iscollapsible' => 'false',
                    'iscollapsed' => 'false',
                    'isinfivisible' => 'false',
                    'isvisible' => 'true',
                    'nrofshownattributes' => 10,
                ],
                'attributes' => [
                    [
                        'title' => 'Red',
                        'url' => 'https://tweakwise.test/default/color/red/',
                        'link' => '',
                        'nrofresults' => 5,
                        'isselected' => 'true',
                        'attributeid' => '100001',
                    ],
                ],
            ];

            /** @var FacetType $facet */
            $facet = $this->tester->getObjectManager()->create(FacetType::class, ['data' => $facetData]);

            /** @var Filter $filter */
            $filter = $this->tester->getObjectManager()->create(
                Filter::class,
                [
                    'layer' => $layer,
                    'facet' => $facet,
                    'attribute' => null,
                ]
            );

            return [$filter];
        });

        $this->tester->mockService(TweakwiseFilterList::class, $service);
    }
}
