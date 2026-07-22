<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Client\Type\FacetType;

use Emico\CodeCept\Test\Unit;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\FacetType\SettingsType;

class SettingsTypeTest extends Unit
{
    /**
     * @return void
     */
    public function testGetTitleReturnsMappedTitleValue(): void
    {
        /** @var SettingsType $settings */
        $settings = $this->tester->getObjectManager()->create(
            SettingsType::class,
            ['data' => ['title' => 'Color']]
        );

        $this->assertSame('Color', $settings->getTitle());
    }

    /**
     * @return void
     */
    public function testGetTitleReturnsEmptyStringWhenTitleIsArray(): void
    {
        /** @var SettingsType $settings */
        $settings = $this->tester->getObjectManager()->create(
            SettingsType::class,
            ['data' => ['title' => ['value' => 'Color']]]
        );

        $this->assertSame('', $settings->getTitle());
    }
}
