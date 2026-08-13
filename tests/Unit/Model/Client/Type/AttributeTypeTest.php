<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Client\Type;

use Emico\CodeCept\Test\Unit;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\AttributeType;

class AttributeTypeTest extends Unit
{
    /**
     * @return void
     */
    public function testGetLinkReturnsMappedLinkValue(): void
    {
        /** @var AttributeType $attribute */
        $attribute = $this->tester->getObjectManager()->create(
            AttributeType::class,
            ['data' => ['link' => 'https://magento2.test/default/women/tops-women2/']]
        );

        $this->assertSame('https://magento2.test/default/women/tops-women2/', $attribute->getLink());
    }

    /**
     * @return void
     */
    public function testGetLinkReturnsEmptyStringWhenLinkIsMissing(): void
    {
        /** @var AttributeType $attribute */
        $attribute = $this->tester->getObjectManager()->create(AttributeType::class);

        $this->assertSame('', $attribute->getLink());
    }
}
