<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Plugin\CustomerData;

use Magento\Customer\CustomerData\Customer as CustomerSection;
use Tweakwise\Magento2Tweakwise\Model\Analytics\CustomerSessionDataProvider;

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
