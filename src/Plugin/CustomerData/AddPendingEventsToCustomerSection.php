<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Plugin\CustomerData;

use Magento\Customer\CustomerData\Customer as CustomerSection;
use Tweakwise\Magento2Tweakwise\Model\Analytics\CustomerSessionDataProvider;

/**
 * Adds any pending addtowishlist events to the generic "customer" customer-data section, so they get
 * pushed to the Tweakwise Event Tag after a wishlist action or the next page load, without waiting for
 * a full page render (see PersonalMerchandisingAnalytics, which deliberately does NOT read this event
 * type, since that block renders on cacheable pages). wishlist/index/add is made to invalidate the
 * "customer" section via etc/frontend/sections.xml, since core only invalidates "wishlist" by default.
 * Mirrors Yireo_GoogleTagManager2's Plugin\AddDataToCustomerSection.
 */
class AddPendingEventsToCustomerSection
{
    public function __construct(
        private readonly CustomerSessionDataProvider $customerSessionDataProvider
    ) {
    }

    public function afterGetSectionData(CustomerSection $subject, $result)
    {
        if (!is_array($result)) {
            return $result;
        }

        $pendingEvents = $this->customerSessionDataProvider->get();
        $this->customerSessionDataProvider->clear();

        if (empty($pendingEvents['addtowishlist_event'])) {
            return $result;
        }

        $result['tweakwise_events'] = array_map(
            fn (array $event) => ['type' => 'addtowishlist_event', 'value' => $event, 'requestId' => ''],
            $pendingEvents['addtowishlist_event']
        );

        return $result;
    }
}
