<?php

declare(strict_types=1);

namespace Tweakwise\Test\Functional;

use Emico\CodeCept\Models\Fixtures\CategoryFixture;
use Emico\CodeCept\Models\Fixtures\ProductFixture;
use Emico\CodeCept\Test\Unit;
use Tweakwise\Test\Support\FunctionalTester;

/**
 * Verifies that each page type's layout XML wiring (catalog_product_view.xml,
 * catalog_category_view.xml, catalogsearch_result_index.xml) actually feeds the right
 * Tag objects into the "tweakwise.analytics" block's "data_layer" argument, by checking the
 * rendered eventsData JSON in the page HTML. This is the class of bug a unit test cannot
 * catch: unit tests instantiate PersonalMerchandisingAnalytics directly and never exercise
 * the di.xml/layout XML wiring that connects a real page to it.
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
        /** @var CategoryFixture $category */
        $category = $this->tester->getObjectManager()->create(CategoryFixture::class, ['name' => 'Analytics Category']);
        $this->tester->createFixture($category);

        $this->tester->amOnPage('/' . $category->getUrlKey() . '.html');

        $this->tester->seeInSource('"type":"page_impression"');
    }

    public function testSearchPageEventsDataContainsSearchAndPageImpressionTags(): void
    {
        $this->tester->amOnPage('/catalogsearch/result/?q=tw-analytics-query');

        $this->tester->seeInSource('"type":"search"');
        $this->tester->seeInSource('"value":"tw-analytics-query"');
        $this->tester->seeInSource('"type":"page_impression"');
    }

    public function testEventsDataIsAbsentWhenAnalyticsIsDisabled(): void
    {
        $this->tester->mockConfig('tweakwise/general/analytics_enabled', '0');

        $this->tester->amOnPage('/catalogsearch/result/?q=tw-analytics-query');

        $this->tester->dontSeeInSource('"type":"search"');
    }
}
