<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Plugin\CustomerData;

use Magento\Checkout\CustomerData\Cart as CartSection;
use Tweakwise\Magento2Tweakwise\Model\Analytics\CheckoutSessionDataProvider;

class AddPendingEventsToCartSection
{
    public function __construct(
        private readonly CheckoutSessionDataProvider $checkoutSessionDataProvider
    ) {
    }

    public function afterGetSectionData(CartSection $subject, array $result): array
    {
        $pendingEvents = $this->checkoutSessionDataProvider->get();
        $this->checkoutSessionDataProvider->clear();

        if (empty($pendingEvents['addtocart_event'])) {
            return $result;
        }

        $result['tweakwise_events'] = [
            ['type' => 'addtocart_event', 'value' => $pendingEvents['addtocart_event'], 'requestId' => ''],
        ];

        return $result;
    }
}
