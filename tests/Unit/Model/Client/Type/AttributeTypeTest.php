<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Client\Type;

use Emico\CodeCept\Test\Unit;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\AttributeType;
use Tweakwise\Test\Support\UnitTester;

class AttributeTypeTest extends Unit
{
    protected UnitTester $tester;

    /**
     * @return void
     */
    public function testGetLinkReturnsMappedLinkValue(): void
    {
        $attribute = new AttributeType(['link' => 'https://magento2.test/default/women/tops-women2/']);

        $this->assertSame('https://magento2.test/default/women/tops-women2/', $attribute->getLink());
    }

    /**
     * @return void
     */
    public function testGetLinkReturnsEmptyStringWhenLinkIsMissing(): void
    {
        $attribute = new AttributeType();

        $this->assertSame('', $attribute->getLink());
    }
}

