<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Config\Backend;

use Emico\CodeCept\Test\Unit;
use Magento\Framework\Exception\LocalizedException;
use Tweakwise\Magento2Tweakwise\Model\Config\Backend\Timeout;
use Tweakwise\Test\Support\UnitTester;

class TimeoutTest extends Unit
{
    protected UnitTester $tester;

    /**
     * @dataProvider invalidValues
     */
    public function testInvalidTimeoutIsRejectedBeforeSaving(string $value): void
    {
        $backend = $this->tester->getObjectManager()->create(Timeout::class);
        $backend->setValue($value);

        $this->expectException(LocalizedException::class);
        $backend->beforeSave();
    }

    public static function invalidValues(): array
    {
        return [['0'], ['-1'], ['invalid'], [''], ['1.5']];
    }

    public function testPositiveIntegerTimeoutIsAccepted(): void
    {
        $backend = $this->tester->getObjectManager()->create(Timeout::class);
        $backend->setValue('3');

        $this->assertSame($backend, $backend->beforeSave());
    }
}
