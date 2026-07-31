<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model\Analytics;

use Magento\Checkout\Model\Session as CheckoutSession;

/**
 * Stashes computed Tweakwise event data (addtocart) directly on the checkout session, so it can be
 * pushed to the Tweakwise Event Tag via the "cart" customer-data section on a later request instead of
 * calling the Tweakwise API directly from the backend. This is the reliability fallback for themes/flows
 * where add-to-cart can't be intercepted client-side (e.g. Hyva's plain form-POST add-to-cart, which has
 * no AJAX moment to hook into) - Luma additionally pushes the same event synchronously client-side (see
 * js/mixins/catalog-add-to-cart-mixin.js), with the push.js de-dup guard collapsing it to one event when
 * both paths deliver it. Mirrors Yireo_GoogleTagManager2's
 * SessionDataProvider\CheckoutSessionDataProvider, which stores its data directly on
 * Magento\Checkout\Model\Session via magic getXxx/setXxx methods.
 */
class CheckoutSessionDataProvider
{
    public function __construct(
        private readonly CheckoutSession $checkoutSession
    ) {
    }

    public function add(string $key, array $data): void
    {
        $events = $this->get();
        $events[$key][] = $data;
        $this->checkoutSession->setTweakwiseGtmData($events);
    }

    public function get(): array
    {
        $events = $this->checkoutSession->getTweakwiseGtmData();

        return is_array($events) ? $events : [];
    }

    public function clear(): void
    {
        $this->checkoutSession->setTweakwiseGtmData([]);
    }
}
