<?php

declare(strict_types=1);

namespace Tweakwise\Test\Functional;

use Emico\CodeCept\Models\Fixtures\CategoryFixture;
use Emico\CodeCept\Test\Unit;
use Mockery;
use Tweakwise\Magento2Tweakwise\Model\Client;
use Tweakwise\Magento2Tweakwise\Model\Client\Response\ProductNavigationResponse;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\FacetType;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\FacetType\SettingsType;
use Tweakwise\Test\Support\FunctionalTester;

class CategoryLinkTest extends Unit
{
    protected FunctionalTester $tester;

    private CategoryFixture $category;

    /**
     * Base URL used in the mocked Tweakwise category link responses.
     */
    private const TWEAKWISE_LINK = 'https://tweakwise.test/default/women/tops-women2/';

    /**
     * @return void
     * phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
     */
    public function _before(): void
    {
        $this->category = new CategoryFixture('Women Tops');
        $this->tester->createFixture($this->category);

        $this->tester->mockConfig('tweakwise/general/enabled', '1');
        $this->tester->mockConfig('tweakwise/layered/enabled', '1');
    }

    /**
     * When the setting is disabled, category facet links must resolve to the store domain,
     * not the raw Tweakwise link field.
     *
     * @return void
     */
    public function testCategoryFacetLinksUseMagentoUrlWhenSettingDisabled(): void
    {
        $this->tester->mockConfig('tweakwise/layered/use_category_url_from_tweakwise', '0');
        $this->mockClientWithCategoryFacet(self::TWEAKWISE_LINK);

        $this->tester->amOnPage('/' . $this->category->getUrlKey() . '.html');

        // Magento store domain must appear in the page (navigation rendered).
        $this->tester->seeInSource('tweakwise.test');
        // A double-domain href indicates the raw Tweakwise URL leaked into a Magento-built URL.
        $this->tester->dontSeeInSource('tweakwise.test/default/http');
        $this->tester->dontSeeInSource('tweakwise.test/default/https');
    }

    /**
     * When the setting is enabled, category facet links must use the Tweakwise-provided link field.
     *
     * @return void
     */
    public function testCategoryFacetLinksUseTweakwiseUrlWhenSettingEnabled(): void
    {
        $this->tester->mockConfig('tweakwise/layered/use_category_url_from_tweakwise', '1');
        $this->mockClientWithCategoryFacet(self::TWEAKWISE_LINK);

        $this->tester->amOnPage('/' . $this->category->getUrlKey() . '.html');

        $this->tester->seeInSource('tweakwise.test');
    }

    /**
     * No duplicated domain must appear regardless of the setting value.
     *
     * @return void
     */
    public function testCategoryFacetLinksContainNoDuplicatedDomain(): void
    {
        $this->tester->mockConfig('tweakwise/layered/use_category_url_from_tweakwise', '1');
        $this->mockClientWithCategoryFacet(self::TWEAKWISE_LINK);

        $this->tester->amOnPage('/' . $this->category->getUrlKey() . '.html');

        $this->tester->dontSeeInSource('tweakwise.testtweakwise.test');
        $this->tester->dontSeeInSource('tweakwise.test//');
    }

    /**
     * On the search results page the raw Tweakwise link field must never be used,
     * even when the setting is enabled.
     *
     * @return void
     */
    public function testCategoryFacetLinksUseMagentoUrlOnSearchResultsPageEvenWhenEnabled(): void
    {
        $this->tester->mockConfig('tweakwise/layered/use_category_url_from_tweakwise', '1');
        $this->mockClientWithCategoryFacet(self::TWEAKWISE_LINK);

        $this->tester->amOnPage('/catalogsearch/result/?q=tops');

        // Search page must not produce a raw category link href.
        $this->tester->seeInSource('catalogsearch');
        $this->tester->dontSeeInSource(self::TWEAKWISE_LINK);
    }

    /**
     * When Tweakwise returns an empty link field the Magento URL must be used as fallback.
     *
     * @return void
     */
    public function testCategoryFacetLinkFallsBackToMagentoUrlWhenTweakwiseLinkIsEmpty(): void
    {
        $this->tester->mockConfig('tweakwise/layered/use_category_url_from_tweakwise', '1');
        $this->mockClientWithCategoryFacet('');

        $this->tester->amOnPage('/' . $this->category->getUrlKey() . '.html');

        // With an empty link the rendered href must not contain a bare slash-slash or be empty.
        $this->tester->dontSeeInSource('href=""');
        $this->tester->dontSeeInSource('tweakwise.test//');
    }

    /**
     * Build a mocked Client that returns a ProductNavigationResponse containing a single
     * category facet with one attribute item whose link field equals $categoryLink.
     *
     * @param string $categoryLink Value for the `link` field on the attribute item.
     * @return void
     */
    private function mockClientWithCategoryFacet(string $categoryLink): void
    {
        $response = $this->buildNavigationResponse($categoryLink);

        $client = Mockery::mock(Client::class);
        $client->shouldReceive('request')->andReturn($response);

        $this->tester->mockService(Client::class, $client);
    }

    /**
     * Build a minimal ProductNavigationResponse with one category facet containing one item.
     *
     * @param string $categoryLink
     * @return ProductNavigationResponse
     */
    private function buildNavigationResponse(string $categoryLink): ProductNavigationResponse
    {
        $facetData = [
            'facetsettings' => [
                'source'       => SettingsType::SOURCE_CATEGORY,
                'title'        => 'Category',
                'urlkey'       => 'cat',
                'selectiontype' => SettingsType::SELECTION_TYPE_LINK,
                'isnrofresultsvisible' => 'false',
                'ismultiselect' => 'false',
                'iscollapsible' => 'false',
                'iscollapsed'  => 'false',
                'isinfivisible' => 'false',
                'isvisible'    => 'true',
                'nrofshownattributes' => 10,
            ],
            'attributes' => [
                [
                    'title'        => 'Tops',
                    'url'          => 'https://tweakwise.test/default/women/tops-women2/',
                    'link'         => $categoryLink,
                    'nrofresults'  => 12,
                    'isselected'   => 'false',
                    'attributeid'  => '100042',
                ],
            ],
        ];

        $facet = new FacetType($facetData);

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

        return $response;
    }
}
