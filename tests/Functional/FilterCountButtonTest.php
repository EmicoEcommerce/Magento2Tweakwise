<?php

declare(strict_types=1);

namespace Tweakwise\Test\Functional;

use Emico\CodeCept\Test\Unit;
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
        $this->tester->mockConfig('tweakwise/layered/form_filters', '1');
    }

    /**
     * @return void
     */
    public function testFilterButtonRendersCountLabelWhenFormFiltersEnabled(): void
    {
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
}
