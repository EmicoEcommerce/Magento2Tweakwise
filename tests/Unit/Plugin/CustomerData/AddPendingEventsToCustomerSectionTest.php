<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Plugin\CustomerData;

use Emico\CodeCept\Test\Unit;
use Magento\Customer\CustomerData\Customer as CustomerSection;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Tweakwise\Magento2Tweakwise\Model\Analytics\CustomerSessionDataProvider;
use Tweakwise\Magento2Tweakwise\Plugin\CustomerData\AddPendingEventsToCustomerSection;

class AddPendingEventsToCustomerSectionTest extends Unit
{
    use MockeryPHPUnitIntegration;

    private CustomerSessionDataProvider&MockInterface $customerSessionDataProvider;
    private CustomerSection&MockInterface $customerSection;
    private AddPendingEventsToCustomerSection $subject;

    protected function _before(): void
    {
        $this->customerSessionDataProvider = Mockery::mock(CustomerSessionDataProvider::class);
        $this->customerSection = Mockery::mock(CustomerSection::class);
        $this->subject = new AddPendingEventsToCustomerSection($this->customerSessionDataProvider);
    }

    public function testAfterGetSectionDataReturnsNonArrayResultUnchangedWithoutTouchingTheStash(): void
    {
        $this->customerSessionDataProvider->shouldNotReceive('get');
        $this->customerSessionDataProvider->shouldNotReceive('clear');

        $result = $this->subject->afterGetSectionData($this->customerSection, null);

        $this->assertNull($result);
    }

    public function testAfterGetSectionDataClearsTheStashButLeavesResultUntouchedWhenNothingIsPending(): void
    {
        $this->customerSessionDataProvider->shouldReceive('get')->once()->andReturn([]);
        $this->customerSessionDataProvider->shouldReceive('clear')->once();

        $result = $this->subject->afterGetSectionData($this->customerSection, ['firstname' => 'Jane']);

        $this->assertSame(['firstname' => 'Jane'], $result);
    }

    public function testAfterGetSectionDataAddsPendingWishlistEventsAsTweakwiseEvents(): void
    {
        $this->customerSessionDataProvider->shouldReceive('get')->once()->andReturn([
            'addtowishlist_event' => [
                ['productKey' => '100016'],
            ],
        ]);
        $this->customerSessionDataProvider->shouldReceive('clear')->once();

        $result = $this->subject->afterGetSectionData($this->customerSection, ['firstname' => 'Jane']);

        $this->assertSame(
            [
                'firstname' => 'Jane',
                'tweakwise_events' => [
                    [
                        'type' => 'addtowishlist_event',
                        'value' => ['productKey' => '100016'],
                        'requestId' => '',
                    ],
                ],
            ],
            $result
        );
    }
}
