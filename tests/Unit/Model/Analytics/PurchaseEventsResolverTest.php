<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Analytics;

use Emico\CodeCept\Test\Unit;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Sales\Model\Order;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Tweakwise\Magento2Tweakwise\Model\Analytics\PurchaseEventDataResolver;
use Tweakwise\Magento2Tweakwise\Model\Analytics\PurchaseEventsResolver;

class PurchaseEventsResolverTest extends Unit
{
    use MockeryPHPUnitIntegration;

    private PurchaseEventDataResolver&MockInterface $purchaseEventDataResolver;
    private CheckoutSession&MockInterface $checkoutSession;
    private LoggerInterface&MockInterface $logger;
    private PurchaseEventsResolver $subject;

    protected function _before(): void
    {
        $this->purchaseEventDataResolver = Mockery::mock(PurchaseEventDataResolver::class);
        $this->checkoutSession = Mockery::mock(CheckoutSession::class);
        $this->logger = Mockery::mock(LoggerInterface::class);

        $this->subject = new PurchaseEventsResolver(
            $this->purchaseEventDataResolver,
            $this->checkoutSession,
            $this->logger
        );
    }

    public function testGetReturnsEmptyArrayWhenThereIsNoRealOrder(): void
    {
        $order = Mockery::mock(Order::class);
        $order->shouldReceive('getId')->once()->andReturn(null);
        $this->checkoutSession->shouldReceive('getLastRealOrder')->once()->andReturn($order);

        $this->purchaseEventDataResolver->shouldNotReceive('resolve');

        $this->assertSame([], $this->subject->get());
    }

    public function testGetWrapsTheResolvedPurchaseEventInAList(): void
    {
        $order = Mockery::mock(Order::class);
        $order->shouldReceive('getId')->once()->andReturn(6);
        $this->checkoutSession->shouldReceive('getLastRealOrder')->once()->andReturn($order);

        $purchaseEvent = ['productKeys' => ['100016'], 'revenue' => 59.0];
        $this->purchaseEventDataResolver->shouldReceive('resolve')->once()->with($order)->andReturn($purchaseEvent);

        $this->assertSame([$purchaseEvent], $this->subject->get());
    }

    public function testGetReturnsEmptyArrayWhenResolvedPurchaseEventIsEmpty(): void
    {
        $order = Mockery::mock(Order::class);
        $order->shouldReceive('getId')->once()->andReturn(6);
        $this->checkoutSession->shouldReceive('getLastRealOrder')->once()->andReturn($order);

        $this->purchaseEventDataResolver->shouldReceive('resolve')->once()->with($order)->andReturn([]);

        $this->assertSame([], $this->subject->get());
    }

    public function testGetSwallowsExceptionsAndLogsThem(): void
    {
        $this->checkoutSession->shouldReceive('getLastRealOrder')->once()->andThrow(new RuntimeException('session unavailable'));
        $this->logger->shouldReceive('error')->once()->with(
            'Tweakwise purchase event could not be resolved',
            ['message' => 'session unavailable']
        );

        $this->assertSame([], $this->subject->get());
    }
}
