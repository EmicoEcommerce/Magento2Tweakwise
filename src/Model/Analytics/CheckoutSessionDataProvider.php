<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model\Analytics;

use Magento\Checkout\Model\Session as CheckoutSession;

class CheckoutSessionDataProvider
{
    public function __construct(
        private readonly CheckoutSession $checkoutSession
    ) {
    }

    public function add(string $key, array $data): void
    {
        $events = $this->get();
        $events[$key] = $data;
        $this->checkoutSession->setTweakwisePendingEvents($events);
    }

    public function get(): array
    {
        $events = $this->checkoutSession->getTweakwisePendingEvents();

        return is_array($events) ? $events : [];
    }

    public function clear(): void
    {
        $this->checkoutSession->setTweakwisePendingEvents([]);
    }
}
