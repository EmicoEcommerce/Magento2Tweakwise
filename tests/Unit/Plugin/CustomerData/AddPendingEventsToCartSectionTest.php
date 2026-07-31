<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Plugin\CustomerData;

use Emico\CodeCept\Test\Unit;
use Magento\Checkout\CustomerData\Cart as CartSection;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Tweakwise\Magento2Tweakwise\Model\Analytics\CheckoutSessionDataProvider;
use Tweakwise\Magento2Tweakwise\Plugin\CustomerData\AddPendingEventsToCartSection;

class AddPendingEventsToCartSectionTest extends Unit
{
    use MockeryPHPUnitIntegration;

    private CheckoutSessionDataProvider&MockInterface $checkoutSessionDataProvider;
    private CartSection&MockInterface $cartSection;
    private AddPendingEventsToCartSection $subject;

    protected function _before(): void
    {
        $this->checkoutSessionDataProvider = Mockery::mock(CheckoutSessionDataProvider::class);
        $this->cartSection = Mockery::mock(CartSection::class);
        $this->subject = new AddPendingEventsToCartSection($this->checkoutSessionDataProvider);
    }

    public function testAfterGetSectionDataReturnsNonArrayResultUnchangedWithoutTouchingTheStash(): void
    {
        $this->checkoutSessionDataProvider->shouldNotReceive('get');
        $this->checkoutSessionDataProvider->shouldNotReceive('clear');

        $result = $this->subject->afterGetSectionData($this->cartSection, false);

        $this->assertFalse($result);
    }

    public function testAfterGetSectionDataClearsTheStashButLeavesResultUntouchedWhenNothingIsPending(): void
    {
        $this->checkoutSessionDataProvider->shouldReceive('get')->once()->andReturn([]);
        $this->checkoutSessionDataProvider->shouldReceive('clear')->once();

        $result = $this->subject->afterGetSectionData($this->cartSection, ['data_id' => 123]);

        $this->assertSame(['data_id' => 123], $result);
    }

    public function testAfterGetSectionDataAddsPendingAddToCartEventsAsTweakwiseEvents(): void
    {
        $this->checkoutSessionDataProvider->shouldReceive('get')->once()->andReturn([
            'addtocart_event' => [
                ['productKey' => '100016', 'quantity' => 1.0, 'totalAmount' => 59.0],
            ],
        ]);
        $this->checkoutSessionDataProvider->shouldReceive('clear')->once();

        $result = $this->subject->afterGetSectionData($this->cartSection, ['data_id' => 123]);

        $this->assertSame(
            [
                'data_id' => 123,
                'tweakwise_events' => [
                    [
                        'type' => 'addtocart_event',
                        'value' => ['productKey' => '100016', 'quantity' => 1.0, 'totalAmount' => 59.0],
                        'requestId' => '',
                    ],
                ],
            ],
            $result
        );
    }
}
