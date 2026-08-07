<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model\Analytics;

class RecommendationImpressionCollector
{
    /**
     * @var string[]
     */
    private array $requestIds = [];

    public function add(string $requestId): void
    {
        if ($requestId === '' || in_array($requestId, $this->requestIds, true)) {
            return;
        }

        $this->requestIds[] = $requestId;
    }

    /**
     * @return string[]
     */
    public function getRequestIds(): array
    {
        return $this->requestIds;
    }
}
