<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model\Analytics;

use Magento\Checkout\Model\Session as CheckoutSession;
use Psr\Log\LoggerInterface;
use Throwable;
use Tweakwise\Magento2Tweakwise\Api\Data\EventInterface;

class PurchaseEventsResolver implements EventInterface
{
    public function __construct(
        private readonly PurchaseEventDataResolver $purchaseEventDataResolver,
        private readonly CheckoutSession $checkoutSession,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Relies on Magento core's own Success::execute()/SuccessValidator to keep this from running twice
     * for the same order: clearQuote() unsets the "last success quote id" after the first render, so
     * reloading the success page redirects to the cart before this ever runs again.
     *
     * @return array[]
     */
    public function get(): array
    {
        try {
            $order = $this->checkoutSession->getLastRealOrder();
            if (!$order->getId()) {
                return [];
            }

            $purchaseEvent = $this->purchaseEventDataResolver->resolve($order);

            return empty($purchaseEvent) ? [] : [$purchaseEvent];
        } catch (Throwable $e) {
            // Never let a transient session issue break the whole success page render.
            $this->logger->error('Tweakwise purchase event could not be resolved', ['message' => $e->getMessage()]);
            return [];
        }
    }
}
