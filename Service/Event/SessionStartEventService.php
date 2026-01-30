<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Service\Event;

use Tweakwise\Magento2Tweakwise\Model\Client\Request\AnalyticsRequest;

class SessionStartEventService extends EventService
{
    /**
     * @param AnalyticsRequest $tweakwiseRequest
     * @return void
     */
    public function handleSessionStartType(AnalyticsRequest $tweakwiseRequest): void
    {
        $this->setTweakwiseSessionKeyCookie();

        $tweakwiseRequest->setParameter('SessionKey', $this->getSessionKey());
        $tweakwiseRequest->setParameter('Source', 'magento2');
        $tweakwiseRequest->setPath('sessionstart');
    }

    /**
     * @return bool
     */
    public function isSessionStartEventSent(): bool
    {
        return (bool) $this->getSessionKey();
    }
}
