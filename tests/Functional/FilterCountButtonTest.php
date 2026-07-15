<?php

declare(strict_types=1);

namespace Tweakwise\Test\Functional;

use Emico\CodeCept\Models\Fixtures\ProductFixture;
use Emico\CodeCept\Test\Unit;
use Mockery;
use Tweakwise\Magento2Tweakwise\Model\Client;
use Tweakwise\Magento2Tweakwise\Model\Client\Response\ProductNavigationResponse;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\FacetType;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\FacetType\SettingsType;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\ItemType;
use Tweakwise\Test\Support\FunctionalTester;

class FilterCountButtonTest extends Unit
{
    protected FunctionalTester $tester;

    private ProductFixture $product;

    // phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
    /**
     * @return void
     */
    public function _before(): void
    {
        /** @var ProductFixture $product */
        $product = $this->tester->getObjectManager()->create(
            ProductFixture::class,
            ['sku' => 'tweakwise-filter-count-button-test']
        );
        $this->product = $product;
        $this->tester->createFixture($this->product);

        $this->tester->mockConfig('tweakwise/general/enabled', '1');
        $this->tester->mockConfig('tweakwise/layered/enabled', '1');
        // Search results page uses Magento\Catalog\Model\Layer\Search; both the filter
        // list plugin and the item collection provider fall back to native Magento
        // behaviour on search pages unless this is enabled, ignoring the mocked client.
        $this->tester->mockConfig('tweakwise/search/enabled', '1');
        $this->mockClientWithCheckboxFacet();
    }

    /**
     * @return void
     */
    public function testFilterButtonRendersCountLabelWhenFormFiltersEnabled(): void
    {
        $this->tester->mockConfig('tweakwise/layered/form_filters', '1');

        $this->tester->amOnPage('/catalogsearch/result/?q=a');
        $this->tester->seeElement('.js-btn-filter[data-count-label]');
    }

    /**
     * @return void
     */
    public function testFilterButtonNotRenderedWhenFormFiltersDisabled(): void
    {
        $this->tester->mockConfig('tweakwise/layered/form_filters', '0');

        $this->tester->amOnPage('/catalogsearch/result/?q=a');
        $this->tester->dontSeeElement('.js-btn-filter[data-count-label]');
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
                    'isselected'   => 'false',
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
                    ItemType::TYPE  => 'visual',
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
}
