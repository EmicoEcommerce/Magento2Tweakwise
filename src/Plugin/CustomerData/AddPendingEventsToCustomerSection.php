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

    public function afterGetSectionData(CustomerSection $subject, array $result): array
    {
        $pendingEvents = $this->customerSessionDataProvider->get();
        $this->customerSessionDataProvider->clear();

        if (empty($pendingEvents['addtowishlist_event'])) {
            return $result;
        }

        $result['tweakwise_events'] = [
            ['type' => 'addtowishlist_event', 'value' => $pendingEvents['addtowishlist_event'], 'requestId' => ''],
        ];

        return $result;
    }
}
