<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Catalog\Product\Recommendation;

use Emico\CodeCept\Test\Unit;
use Mockery;
use Tweakwise\Magento2Tweakwise\Exception\ApiException;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Product\Recommendation\Context;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Product\Recommendation\ProfileKeyApplier;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\Recommendations\FeaturedRequest;
use Tweakwise\Magento2Tweakwise\Model\Client\RequestPool;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Test\Support\UnitTester;

class ContextTest extends Unit
{
    protected UnitTester $tester;

    public function testDoesNotResolveRecommendationsAfterAnApiFailure(): void
    {
        $config = Mockery::mock(Config::class);
        $config->shouldReceive('getTweakwiseExceptionTrown')->andReturn(true);
        $pool = $this->createMock(RequestPool::class);
        $pool->expects($this->never())->method('resolve');
        $profileKeyApplier = Mockery::mock(ProfileKeyApplier::class);
        $profileKeyApplier->shouldReceive('apply');
        $subject = $this->tester->getObjectManager()->create(Context::class, [
            'config' => $config,
            'requestPool' => $pool,
            'profileKeyApplier' => $profileKeyApplier,
        ]);
        $subject->setRequest(Mockery::mock(FeaturedRequest::class));

        $this->expectException(ApiException::class);
        $subject->getResponse();
    }
}
