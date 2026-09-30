<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Etc;

use Emico\CodeCept\Test\Unit;
use ReflectionClass;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Test\Support\UnitTester;

class DiXmlTest extends Unit
{
    protected UnitTester $tester;

    public function testUpsellAndRelatedRecommendationContextsAreNotShared(): void
    {
        $configReflection = new ReflectionClass(Config::class);
        $diXmlPath = dirname((string) $configReflection->getFileName(), 2) . '/etc/di.xml';

        $this->assertFileExists($diXmlPath);

        $document = simplexml_load_file($diXmlPath);

        $this->assertNotFalse($document);

        $upsellContexts = $document->xpath('/config/virtualType[@name="Tweakwise\\Magento2Tweakwise\\Model\\Catalog\\Product\\Recommendation\\Context\\Product\\Upsell"]');
        $relatedContexts = $document->xpath('/config/virtualType[@name="Tweakwise\\Magento2Tweakwise\\Model\\Catalog\\Product\\Recommendation\\Context\\Product\\Related"]');

        $this->assertCount(1, $upsellContexts);
        $this->assertCount(1, $relatedContexts);
        $this->assertSame('false', (string) $upsellContexts[0]->attributes()->shared);
        $this->assertSame('false', (string) $relatedContexts[0]->attributes()->shared);
    }

    public function testAllCategoryLayerContextsUseTweakwiseCollectionProvider(): void
    {
        $configReflection = new ReflectionClass(Config::class);
        $diXmlPath = dirname((string) $configReflection->getFileName(), 2) . '/etc/di.xml';
        $document = simplexml_load_file($diXmlPath);

        $this->assertNotFalse($document);

        $contextNames = [
            'Magento\Catalog\Model\Layer\Category\Context',
            'Magento\CatalogSearch\Model\Layer\Category\Context',
            'Magento\Elasticsearch\Model\Layer\Category\Context',
        ];

        foreach ($contextNames as $contextName) {
            $contexts = $document->xpath(
                sprintf('/config/virtualType[@name="%s"]/arguments/argument[@name="collectionProvider"]', $contextName)
            );

            $this->assertCount(1, $contexts, sprintf('Missing category context override for %s', $contextName));
            $this->assertSame(
                'Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\ItemCollectionProvider\Category',
                trim((string) $contexts[0])
            );
        }
    }
}
