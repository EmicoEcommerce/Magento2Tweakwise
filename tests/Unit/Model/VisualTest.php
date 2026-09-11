<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model;

use Emico\CodeCept\Test\Unit;
use ReflectionClass;
use Tweakwise\Magento2Tweakwise\Model\Visual;

class VisualTest extends Unit
{
    public function testGetVisualAttributesReturnsEmptyArrayWhenNotSet(): void
    {
        $subject = $this->createSubject();

        $this->assertSame([], $subject->getVisualAttributes());
    }

    public function testSetVisualAttributesStoresDataAndReturnsSubject(): void
    {
        $subject = $this->createSubject();
        $attributes = [
            'color' => 'red',
            'sizes' => ['m', 'l'],
        ];

        $result = $subject->setVisualAttributes($attributes);

        $this->assertSame($subject, $result);
        $this->assertSame($attributes, $subject->getVisualAttributes());
    }

    private function createSubject(): Visual
    {
        $reflection = new ReflectionClass(Visual::class);

        /** @var Visual $subject */
        $subject = $reflection->newInstanceWithoutConstructor();
        return $subject;
    }
}
