<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model\Analytics;

use Magento\Customer\Model\Session as CustomerSession;

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
