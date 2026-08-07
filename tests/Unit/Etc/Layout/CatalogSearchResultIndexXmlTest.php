<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Etc\Layout;

use Emico\CodeCept\Test\Unit;
use ReflectionClass;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Test\Support\UnitTester;

class CatalogSearchResultIndexXmlTest extends Unit
{
    protected UnitTester $tester;

    public function testSearchResultListContainsVisualRendererBlock(): void
    {
        $configReflection = new ReflectionClass(Config::class);
        $layoutXmlPath = dirname((string) $configReflection->getFileName(), 2)
            . '/view/frontend/layout/catalogsearch_result_index.xml';

        $this->assertFileExists($layoutXmlPath);

        $document = simplexml_load_file($layoutXmlPath);

        $this->assertNotFalse($document);

        $productItemBlocks = $document->xpath('/page/body/referenceBlock[@name="search_result_list"]/block[@name="tweakwise.catalog.product.list.item"]');
        $visualBlocks = $document->xpath('/page/body/referenceBlock[@name="search_result_list"]/block[@name="tweakwise.catalog.product.list.visual"]');

        $this->assertCount(1, $productItemBlocks);
        $this->assertCount(1, $visualBlocks);
        $this->assertSame(
            'Tweakwise_Magento2Tweakwise::product/list/visual.phtml',
            (string) $visualBlocks[0]->attributes()->template
        );
    }
}
