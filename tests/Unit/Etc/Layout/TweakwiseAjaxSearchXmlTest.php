<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Etc\Layout;

use Emico\CodeCept\Test\Unit;
use ReflectionClass;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2Tweakwise\Model\NavigationConfig\Search;

class TweakwiseAjaxSearchXmlTest extends Unit
{
    /**
     * columns.top is not part of the ajax response, navigation-form.js clears banner placeholders missing from it
     */
    public function testAjaxResponseContainsTheContainerTopSearchBanner(): void
    {
        $configReflection = new ReflectionClass(Config::class);
        $document = simplexml_load_file(
            dirname((string)$configReflection->getFileName(), 2) . '/view/frontend/layout/tweakwise_ajax_search.xml'
        );

        $this->assertNotFalse($document);

        $blocks = $document->xpath(
            '/layout/referenceContainer[@name="content"]/block[@name="navigation.search.banner.container.top"]'
        );

        $this->assertCount(1, $blocks);
        $this->assertSame('tweakwise/search/searchbanner', (string)$blocks[0]->attributes()->ifconfig);
        $this->assertSame(
            'Tweakwise_Magento2Tweakwise::searchbanner/search.banner.container.top.phtml',
            (string)$blocks[0]->attributes()->template
        );
        $this->assertSame(
            Search::class,
            (string)$blocks[0]->arguments->argument[0]
        );
    }
}
