<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Catalog\Product\Recommendation;

use Emico\CodeCept\Test\Unit;
use Magento\Catalog\Model\Product;
use Mockery;
use Mockery\MockInterface;
use Tweakwise\Magento2Tweakwise\Exception\ApiException;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Product\Recommendation\ProfileKeyApplier;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Product\Recommendation\RequestPrefetcher;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\Recommendations\ProductRequest;
use Tweakwise\Magento2Tweakwise\Model\Client\RequestFactory;
use Tweakwise\Magento2Tweakwise\Model\Client\RequestPool;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2Tweakwise\Model\Config\TemplateFinder;
use Tweakwise\Test\Support\UnitTester;

class RequestPrefetcherTest extends Unit
{
    protected UnitTester $tester;

    private Config&MockInterface $config;
    private TemplateFinder&MockInterface $templateFinder;
    private RequestFactory&MockInterface $requestFactory;
    private RequestPool&MockInterface $requestPool;
    private ProfileKeyApplier&MockInterface $profileKeyApplier;
    private RequestPrefetcher $prefetcher;
    private Product&MockInterface $product;

    public function _before(): void
    {
        $this->config = Mockery::mock(Config::class);
        $this->templateFinder = Mockery::mock(TemplateFinder::class);
        $this->requestFactory = Mockery::mock(RequestFactory::class);
        $this->requestPool = Mockery::mock(RequestPool::class);
        $this->profileKeyApplier = Mockery::mock(ProfileKeyApplier::class);
        $this->prefetcher = new RequestPrefetcher(
            $this->config,
            $this->templateFinder,
            $this->requestFactory,
            $this->requestPool,
            $this->profileKeyApplier,
        );

        $this->product = Mockery::mock(Product::class);
        $this->product->shouldReceive('getId')->andReturn(123);
    }

    public function _after(): void
    {
        Mockery::close();
    }

    public function testQueuesTheOtherRecommendationTypesOfTheProductOnce(): void
    {
        $this->config->shouldReceive('isRecommendationsBatchingEnabled')->andReturn(true);
        $this->config->shouldReceive('isRecommendationsEnabled')
            ->with(Config::RECOMMENDATION_TYPE_CROSSSELL)->andReturn(true);
        $this->config->shouldNotReceive('isRecommendationsEnabled')->with(Config::RECOMMENDATION_TYPE_UPSELL);
        $this->templateFinder->shouldReceive('forProduct')
            ->with($this->product, Config::RECOMMENDATION_TYPE_CROSSSELL)->once()->andReturn(20);

        $request = Mockery::mock(ProductRequest::class);
        $request->shouldReceive('setProduct')->with($this->product)->once()->andReturnSelf();
        $request->shouldReceive('setTemplate')->with(20)->once()->andReturnSelf();
        $this->requestFactory->shouldReceive('create')->once()->andReturn($request);
        $this->profileKeyApplier->shouldReceive('apply')->with($request)->once();
        $this->requestPool->shouldReceive('add')->with($request)->once();

        $this->prefetcher->prefetchForProduct($this->product, Config::RECOMMENDATION_TYPE_UPSELL);
        // A second block for the same product does not trigger another template lookup or request.
        $this->prefetcher->prefetchForProduct($this->product, Config::RECOMMENDATION_TYPE_UPSELL);
    }

    public function testDoesNothingWhenBatchingIsDisabled(): void
    {
        $this->config->shouldReceive('isRecommendationsBatchingEnabled')->andReturn(false);
        $this->config->shouldNotReceive('isRecommendationsEnabled');
        $this->requestPool->shouldNotReceive('add');

        $this->prefetcher->prefetchForProduct($this->product, Config::RECOMMENDATION_TYPE_UPSELL);
    }

    public function testSkipsDisabledTypesAndTypesWithoutTemplate(): void
    {
        $this->config->shouldReceive('isRecommendationsBatchingEnabled')->andReturn(true);
        $this->config->shouldReceive('isRecommendationsEnabled')
            ->with(Config::RECOMMENDATION_TYPE_UPSELL)->andReturn(false);
        $this->config->shouldReceive('isRecommendationsEnabled')
            ->with(Config::RECOMMENDATION_TYPE_CROSSSELL)->andReturn(true);
        $this->templateFinder->shouldReceive('forProduct')
            ->with($this->product, Config::RECOMMENDATION_TYPE_CROSSSELL)->once()->andReturn(0);
        $this->requestFactory->shouldNotReceive('create');
        $this->requestPool->shouldNotReceive('add');

        $this->prefetcher->prefetchForProduct($this->product);
    }

    public function testFailureToQueueASpeculativeRequestIsSwallowed(): void
    {
        $this->config->shouldReceive('isRecommendationsBatchingEnabled')->andReturn(true);
        $this->config->shouldReceive('isRecommendationsEnabled')->andReturn(true);
        $this->templateFinder->shouldReceive('forProduct')->andReturn(10, 20);

        $request = Mockery::mock(ProductRequest::class);
        $request->shouldReceive('setProduct')->andReturnSelf();
        $request->shouldReceive('setTemplate')->andReturnSelf();
        $this->requestFactory->shouldReceive('create')->twice()->andReturn($request);
        $this->profileKeyApplier->shouldReceive('apply')->twice();
        $this->requestPool->shouldReceive('add')->twice()->andThrow(new ApiException('boom'));

        $this->prefetcher->prefetchForProduct($this->product);
    }
}
