<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Analytics;

use Emico\CodeCept\Test\Unit;
use Magento\Customer\Model\Session as CustomerSession;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Tweakwise\Magento2Tweakwise\Model\Analytics\CustomerSessionDataProvider;

class CustomerSessionDataProviderTest extends Unit
{
    use MockeryPHPUnitIntegration;

    private CustomerSession&MockInterface $session;
    private CustomerSessionDataProvider $subject;

    protected function _before(): void
    {
        $this->session = Mockery::mock(CustomerSession::class);
        $this->subject = new CustomerSessionDataProvider($this->session);
    }

    public function testGetReturnsEmptyArrayWhenNothingIsStoredYet(): void
    {
        $this->session->shouldReceive('getTweakwisePendingEvents')->once()->andReturn(null);

        $this->assertSame([], $this->subject->get());
    }

    public function testAddAppendsToAnExistingListForTheSameKeyInsteadOfOverwriting(): void
    {
        $this->session->shouldReceive('getTweakwisePendingEvents')->once()->andReturn([
            'addtowishlist_event' => [['productKey' => 'first']],
        ]);

        $this->session->shouldReceive('setTweakwisePendingEvents')->once()->with([
            'addtowishlist_event' => [
                ['productKey' => 'first'],
                ['productKey' => 'second'],
            ],
        ]);

        $this->subject->add('addtowishlist_event', ['productKey' => 'second']);
    }

    public function testClearResetsStorageToAnEmptyArray(): void
    {
        $this->session->shouldReceive('setTweakwisePendingEvents')->once()->with([]);

        $this->subject->clear();
    }
}
