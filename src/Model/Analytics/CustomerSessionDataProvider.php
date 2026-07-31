<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model\Analytics;

use Magento\Customer\Model\Session as CustomerSession;

/**
 * Stashes computed Tweakwise event data (addtowishlist) directly on the customer session, so it can be
 * pushed to the Tweakwise Event Tag on a later request instead of calling the Tweakwise API directly
 * from the backend. Mirrors Yireo_GoogleTagManager2's SessionDataProvider\CustomerSessionDataProvider,
 * which stores its data directly on Magento\Customer\Model\Session via magic getXxx/setXxx methods,
 * rather than a dedicated session namespace.
 */
class CustomerSessionDataProvider
{
    public function __construct(
        private readonly CustomerSession $customerSession
    ) {
    }

    public function add(string $key, array $data): void
    {
        $events = $this->get();
        $events[$key][] = $data;
        $this->customerSession->setTweakwiseGtmData($events);
    }

    public function get(): array
    {
        $events = $this->customerSession->getTweakwiseGtmData();

        return is_array($events) ? $events : [];
    }

    public function clear(): void
    {
        $this->customerSession->setTweakwiseGtmData([]);
    }
}
