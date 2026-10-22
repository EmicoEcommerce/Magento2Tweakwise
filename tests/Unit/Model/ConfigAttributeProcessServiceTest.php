<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Tweakwise\Magento2Tweakwise\Model\ConfigAttributeProcessService;

class ConfigAttributeProcessServiceTest extends TestCase
{
    public function testFilterWhitelistNormalizesAttributeCodes(): void
    {
        $this->assertSame(
            ['gepeld_ongepeld', 'color', 'size'],
            ConfigAttributeProcessService::extractFilterWhitelist('Gepeld_ongepeld,COLOR,size')
        );
    }

    public function testValueWhitelistMergesAttributeCodesWithoutChangingValues(): void
    {
        $this->assertSame(
            ['gepeld_ongepeld' => ['Gepeld', 'Ongepeld']],
            ConfigAttributeProcessService::extractFilterValuesWhitelist(
                'Gepeld_ongepeld=Gepeld,GEPELD_ONGEPELD=Ongepeld'
            )
        );
    }

    public function testEmptyWhitelistsRemainEmpty(): void
    {
        $this->assertSame([], ConfigAttributeProcessService::extractFilterWhitelist(null));
        $this->assertSame([], ConfigAttributeProcessService::extractFilterWhitelist(''));
        $this->assertSame([], ConfigAttributeProcessService::extractFilterValuesWhitelist(null));
        $this->assertSame([], ConfigAttributeProcessService::extractFilterValuesWhitelist(''));
    }
}
