<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Plugin\CustomerData;

use Magento\Checkout\CustomerData\Cart as CartSection;
use Tweakwise\Magento2Tweakwise\Model\Analytics\CheckoutSessionDataProvider;

/**
 * Adds any pending addtocart events to the "cart" customer-data section, so they get pushed to the
 * Tweakwise Event Tag after an add-to-cart action or the next page load, without waiting for a full
 * page render (see PersonalMerchandisingAnalytics, which deliberately does NOT read this event type,
 * since that block renders on cacheable pages). This is the reliability fallback for flows without an
 * AJAX moment to hook into client-side (e.g. Hyva's plain form-POST add-to-cart); Luma additionally gets
 * the same event synchronously via js/mixins/catalog-add-to-cart-mixin.js, deduplicated by push.js.
 * Mirrors Yireo_GoogleTagManager2's Plugin\AddDataToCartSection.
 */
class AddPendingEventsToCartSection
{
    public function __construct(
        private readonly CheckoutSessionDataProvider $checkoutSessionDataProvider
    ) {
    }

    public function afterGetSectionData(CartSection $subject, $result)
    {
        if (!is_array($result)) {
            return $result;
        }

        $pendingEvents = $this->checkoutSessionDataProvider->get();
        $this->checkoutSessionDataProvider->clear();

        if (empty($pendingEvents['addtocart_event'])) {
            return $result;
        }

        $result['tweakwise_events'] = array_map(
            fn (array $event) => ['type' => 'addtocart_event', 'value' => $event, 'requestId' => ''],
            $pendingEvents['addtocart_event']
        );

        return $result;
    }
}
