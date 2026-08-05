<?php

declare(strict_types=1);

namespace Tweakwise\Test\Functional;

use Emico\CodeCept\Models\Fixtures\CategoryFixture;
use Emico\CodeCept\Models\Fixtures\ProductFixture;
use Emico\CodeCept\Test\Unit;
use Mockery;
use Tweakwise\Magento2Tweakwise\Model\Client;
use Tweakwise\Magento2Tweakwise\Model\Client\Response\ProductNavigationResponse;
use Tweakwise\Test\Support\FunctionalTester;

/**
 * Verifies that each page type's layout XML wiring (catalog_product_view.xml,
 * catalog_category_view.xml, catalogsearch_result_index.xml) actually feeds the right
 * Tag objects into the "tweakwise.analytics" block's "data_layer" argument, by checking the
 * rendered eventsData JSON in the page HTML. This is the class of bug a unit test cannot
 * catch: unit tests instantiate PersonalMerchandisingAnalytics directly and never exercise
 * the di.xml/layout XML wiring that connects a real page to it.
 *
 * Category/search pages mock the Tweakwise Client (see mockTweakwiseClient()) so they render
 * from a canned response instead of calling the real gateway.tweakwisenavigator.net over the
 * network - the eventsData tags asserted here come entirely from request params/config, never
 * from navigation response content, so a minimal empty response is sufficient. The product page
 * doesn't go through NavigationContext at all, so it needs no such mock.
 */
class PersonalMerchandisingAnalyticsEventsDataTest extends Unit
{
    protected FunctionalTester $tester;

    /**
     * phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
     */
    public function _before(): void
    {
        $this->tester->mockConfig('tweakwise/general/analytics_enabled', '1');
    }

    public function testProductPageEventsDataContainsAProductViewTag(): void
    {
        /** @var ProductFixture $product */
        $product = $this->tester->getObjectManager()->create(ProductFixture::class, ['sku' => 'tw-analytics-product']);
        $this->tester->createFixture($product);

        $this->tester->amOnPage('/' . $product->getUrlKey() . '.html');

        $this->tester->seeInSource('"type":"product"');
    }

    public function testCategoryPageEventsDataContainsAPageImpressionTag(): void
    {
        $this->mockTweakwiseClient();

        /** @var CategoryFixture $category */
        $category = $this->tester->getObjectManager()->create(CategoryFixture::class, ['name' => 'Analytics Category']);
        $this->tester->createFixture($category);

        $this->tester->amOnPage('/' . $category->getUrlKey() . '.html');

        $this->tester->seeInSource('"type":"page_impression"');
    }

    public function testSearchPageEventsDataContainsSearchAndPageImpressionTags(): void
    {
        $this->mockTweakwiseClient();

        $this->tester->amOnPage('/catalogsearch/result/?q=tw-analytics-query');

        $this->tester->seeInSource('"type":"search"');
        $this->tester->seeInSource('"value":"tw-analytics-query"');
        $this->tester->seeInSource('"type":"page_impression"');
    }

    public function testEventsDataIsAbsentWhenAnalyticsIsDisabled(): void
    {
        $this->mockTweakwiseClient();
        $this->tester->mockConfig('tweakwise/general/analytics_enabled', '0');

        $this->tester->amOnPage('/catalogsearch/result/?q=tw-analytics-query');

        $this->tester->dontSeeInSource('"type":"search"');
    }

    private function mockTweakwiseClient(): void
    {
        $response = $this->tester->getObjectManager()->create(
            ProductNavigationResponse::class,
            [
                'data' => [
                    'facets'     => [],
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
