<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model\Analytics;

use Magento\Checkout\Model\Session as CheckoutSession;
use Psr\Log\LoggerInterface;
use Throwable;
use Tweakwise\Magento2Tweakwise\Api\Data\EventInterface;

/**
 * Resolves the purchase event for the checkout success page. Wired only into checkout_onepage_success.xml's
 * "data_layer_events" argument, never into every page's "tweakwise.analytics" block like the Tag\*
 * classes, so the checkout session it depends on is never touched on pages that don't need it. Always
 * resolves the order directly, mirroring Yireo_GoogleTagManager2's DataLayer\Event\Purchase::get(),
 * which never reads a session stash for this event either.
 */
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
