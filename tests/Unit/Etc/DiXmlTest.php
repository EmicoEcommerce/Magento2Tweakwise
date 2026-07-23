<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Etc;

use Emico\CodeCept\Test\Unit;
use Tweakwise\Test\Support\UnitTester;

class DiXmlTest extends Unit
{
    protected UnitTester $tester;

    public function testUpsellAndRelatedRecommendationContextsAreNotShared(): void
    {
        $diXmlPath = dirname(__DIR__, 4) . '/src/etc/di.xml';
        $document = simplexml_load_file($diXmlPath);

        $this->assertNotFalse($document);

        $upsellContexts = $document->xpath('/config/virtualType[@name="Tweakwise\\Magento2Tweakwise\\Model\\Catalog\\Product\\Recommendation\\Context\\Product\\Upsell"]');
        $relatedContexts = $document->xpath('/config/virtualType[@name="Tweakwise\\Magento2Tweakwise\\Model\\Catalog\\Product\\Recommendation\\Context\\Product\\Related"]');

        $this->assertCount(1, $upsellContexts);
        $this->assertCount(1, $relatedContexts);
        $this->assertSame('false', (string) $upsellContexts[0]->attributes()->shared);
        $this->assertSame('false', (string) $relatedContexts[0]->attributes()->shared);
    }
}
