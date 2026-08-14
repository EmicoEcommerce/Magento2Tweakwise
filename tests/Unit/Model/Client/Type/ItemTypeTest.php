<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Client\Type;

use Emico\CodeCept\Test\Unit;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\ItemType;

class ItemTypeTest extends Unit
{
    /**
     * @return void
     */
    public function testGetAttributeValuesReturnsEmptyArrayWhenAttributesAreMissing(): void
    {
        /** @var ItemType $item */
        $item = $this->tester->getObjectManager()->create(ItemType::class);

        $this->assertSame([], $item->getAttributeValues());
    }

    /**
     * @return void
     */
    public function testGetAttributeValuesReturnsFlatMapForSingleValueAttributes(): void
    {
        /** @var ItemType $item */
        $item = $this->tester->getObjectManager()->create(
            ItemType::class,
            [
                'data' => [
                    ItemType::ATTRIBUTES => [
                        'attribute' => [
                            'name' => 'color',
                            'values' => [
                                'value' => 'red'
                            ]
                        ]
                    ]
                ]
            ]
        );

        $this->assertSame(['color' => 'red'], $item->getAttributeValues());
    }

    /**
     * @return void
     */
    public function testGetAttributeValuesReturnsStringArrayForMultiValueAttributes(): void
    {
        /** @var ItemType $item */
        $item = $this->tester->getObjectManager()->create(
            ItemType::class,
            [
                'data' => [
                    ItemType::ATTRIBUTES => [
                        'attribute' => [
                            [
                                'name' => 'size',
                                'values' => [
                                    'value' => [42, true, 'XL']
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        );

        $this->assertSame(['size' => ['42', '1', 'XL']], $item->getAttributeValues());
    }

    /**
     * @return void
     */
    public function testGetAttributeValuesSkipsInvalidAttributes(): void
    {
        /** @var ItemType $item */
        $item = $this->tester->getObjectManager()->create(
            ItemType::class,
            [
                'data' => [
                    ItemType::ATTRIBUTES => [
                        'attribute' => [
                            [
                                'values' => [
                                    'value' => 'missing-name'
                                ]
                            ],
                            [
                                'name' => 'missing-values'
                            ],
                            [
                                'name' => 'material',
                                'values' => [
                                    'value' => 'cotton'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        );

        $this->assertSame(['material' => 'cotton'], $item->getAttributeValues());
    }
}
