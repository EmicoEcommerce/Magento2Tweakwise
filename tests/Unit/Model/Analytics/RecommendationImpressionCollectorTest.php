<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Analytics;

use Emico\CodeCept\Test\Unit;
use Tweakwise\Magento2Tweakwise\Model\Analytics\RecommendationImpressionCollector;

class RecommendationImpressionCollectorTest extends Unit
{
    private RecommendationImpressionCollector $subject;

    protected function _before(): void
    {
        $this->subject = new RecommendationImpressionCollector();
    }

    public function testGetRequestIdsReturnsEmptyArrayWhenNothingWasAdded(): void
    {
        $this->assertSame([], $this->subject->getRequestIds());
    }

    public function testAddCollectsDistinctRequestIdsInOrder(): void
    {
        $this->subject->add('upsell-req');
        $this->subject->add('related-req');

        $this->assertSame(['upsell-req', 'related-req'], $this->subject->getRequestIds());
    }

    public function testAddIgnoresARequestIdThatWasAlreadyAdded(): void
    {
        $this->subject->add('upsell-req');
        $this->subject->add('related-req');
        $this->subject->add('upsell-req');

        $this->assertSame(['upsell-req', 'related-req'], $this->subject->getRequestIds());
    }

    public function testAddIgnoresAnEmptyRequestId(): void
    {
        $this->subject->add('');

        $this->assertSame([], $this->subject->getRequestIds());
    }
}
