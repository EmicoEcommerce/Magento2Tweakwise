<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Analytics;

use Emico\CodeCept\Test\Unit;
use Magento\Checkout\Model\Session as CheckoutSession;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Tweakwise\Magento2Tweakwise\Model\Analytics\CheckoutSessionDataProvider;

class CheckoutSessionDataProviderTest extends Unit
{
    use MockeryPHPUnitIntegration;

    private CheckoutSession&MockInterface $session;
    private CheckoutSessionDataProvider $subject;

    protected function _before(): void
    {
        $this->session = Mockery::mock(CheckoutSession::class);
        $this->subject = new CheckoutSessionDataProvider($this->session);
    }

    public function testGetReturnsEmptyArrayWhenNothingIsStoredYet(): void
    {
        $this->session->shouldReceive('getTweakwisePendingEvents')->once()->andReturn(null);

        $this->assertSame([], $this->subject->get());
    }

    public function testAddAppendsToAnExistingListForTheSameKeyInsteadOfOverwriting(): void
    {
        $this->session->shouldReceive('getTweakwisePendingEvents')->once()->andReturn([
            'addtocart_event' => [['productKey' => 'first']],
        ]);

        $this->session->shouldReceive('setTweakwisePendingEvents')->once()->with([
            'addtocart_event' => [
                ['productKey' => 'first'],
                ['productKey' => 'second'],
            ],
        ]);

        $this->subject->add('addtocart_event', ['productKey' => 'second']);
    }

    public function testAddCreatesTheKeyWhenNothingWasStoredYet(): void
    {
        $this->session->shouldReceive('getTweakwisePendingEvents')->once()->andReturn(null);
        $this->session->shouldReceive('setTweakwisePendingEvents')->once()->with([
            'addtocart_event' => [['productKey' => '100016']],
        ]);

        $this->subject->add('addtocart_event', ['productKey' => '100016']);
    }

    public function testClearResetsStorageToAnEmptyArray(): void
    {
        $this->session->shouldReceive('setTweakwisePendingEvents')->once()->with([]);

        $this->subject->clear();
    }
}
