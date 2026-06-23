<?php

declare(strict_types=1);

namespace Tweakwise\Test\Functional;

use Emico\CodeCept\Test\Unit;
use Tweakwise\Test\Support\FunctionalTester;

class CategoryLinkTest extends Unit
{
    protected FunctionalTester $tester;

    /**
     * @return void
     */
    public function testCategoryFacetLinksUseMagentoUrlWhenSettingDisabled(): void
    {
        $this->tester->setConfig('tweakwise/layered/use_category_url_from_tweakwise', '0');

        $this->tester->amOnPage('/default/women/tops-women2/');
        $this->tester->dontSeeInSource('http://');
    }

    /**
     * @return void
     */
    public function testCategoryFacetLinksUseTweakwiseUrlWhenSettingEnabled(): void
    {
        $this->tester->setConfig('tweakwise/layered/use_category_url_from_tweakwise', '1');

        $this->tester->amOnPage('/default/women/tops-women2/');
        $this->tester->seeInSource('tweakwise.test');
    }

    /**
     * @return void
     */
    public function testCategoryFacetLinksContainNoDuplicatedDomain(): void
    {
        $this->tester->setConfig('tweakwise/layered/use_category_url_from_tweakwise', '1');

        $this->tester->amOnPage('/default/women/tops-women2/');
        $this->tester->dontSeeInSource('tweakwise.testtweakwise.test');
        $this->tester->dontSeeInSource('tweakwise.test//');
    }

    /**
     * @return void
     */
    public function testCategoryFacetLinksUseMagentoUrlOnSearchResultsPageEvenWhenEnabled(): void
    {
        $this->tester->setConfig('tweakwise/layered/use_category_url_from_tweakwise', '1');

        $this->tester->amOnPage('/catalogsearch/result/?q=top');
        // Search page must not leak the raw Tweakwise link field (which would be category page URL)
        $this->tester->seeInSource('catalogsearch');
    }
}
