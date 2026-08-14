<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Analytics;

use Emico\CodeCept\Test\Unit;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Tweakwise\Magento2Tweakwise\Model\Analytics\ProductKeyResolver;
use Tweakwise\Magento2TweakwiseExport\Model\Helper;

class ProductKeyResolverTest extends Unit
{
    use MockeryPHPUnitIntegration;

    private Helper&MockInterface $helper;
    private ProductKeyResolver $subject;

    protected function _before(): void
    {
        $this->helper = Mockery::mock(Helper::class);
        $this->subject = new ProductKeyResolver($this->helper);
    }

    public function testResolveIgnoresDelimiterWhenGroupedProductsAreDisabled(): void
    {
        $this->helper->shouldReceive('getTweakwiseId')->once()->with(1, 42)->andReturn('10001042');

        $result = $this->subject->resolve('42-9', 1, false);

        $this->assertSame('10001042', $result);
    }

    public function testResolveIsSelfReferencingWhenRawIdHasNoGroupCode(): void
    {
        $this->helper->shouldReceive('getTweakwiseId')->once()->with(1, 42)->andReturn('10001042');

        $result = $this->subject->resolve('42', 1, true);

        $this->assertSame('10001042-10001042', $result);
    }

    public function testResolveCombinesItemAndGroupTweakwiseIds(): void
    {
        $this->helper->shouldReceive('getTweakwiseId')->once()->with(1, 5)->andReturn('10001005');
        $this->helper->shouldReceive('getTweakwiseId')->once()->with(1, 9)->andReturn('10001009');

        $result = $this->subject->resolve('5-9', 1, true);

        $this->assertSame('10001005-10001009', $result);
    }
}
