<?php

declare(strict_types=1);

namespace Tweakwise\Test\Functional;

use Emico\CodeCept\Test\Unit;
use Mockery;
use Tweakwise\Magento2Tweakwise\Model\Client;
use Tweakwise\Magento2Tweakwise\Model\Client\Response\ProductNavigationResponse;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\FacetType;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\FacetType\SettingsType;
use Tweakwise\Test\Support\FunctionalTester;

class FilterCountButtonTest extends Unit
{
    protected FunctionalTester $tester;

    // phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
    /**
     * @return void
     */
    public function _before(): void
    {
        $this->tester->mockConfig('tweakwise/general/enabled', '1');
        $this->tester->mockConfig('tweakwise/layered/enabled', '1');
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

        /** @var ProductNavigationResponse $response */
        $response = $this->tester->getObjectManager()->create(
            ProductNavigationResponse::class,
            [
                'data' => [
                    'facets'     => [$facet],
                    'items'      => [],
                    'properties' => [
                        'nrofitems'          => 0,
                        'nrofpages'          => 0,
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
