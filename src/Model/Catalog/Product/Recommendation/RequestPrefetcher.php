<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model\Catalog\Product\Recommendation;

use Magento\Catalog\Model\Product;
use Tweakwise\Magento2Tweakwise\Exception\ApiException;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\Recommendations\ProductRequest;
use Tweakwise\Magento2Tweakwise\Model\Client\RequestFactory;
use Tweakwise\Magento2Tweakwise\Model\Client\RequestPool;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2Tweakwise\Model\Config\TemplateFinder;

/**
 * Queues the recommendation requests of the other enabled recommendation types for a product in the request pool,
 * so that all recommendation calls of a product page are sent to Tweakwise at once instead of one after another.
 */
class RequestPrefetcher
{
    private const PRODUCT_TYPES = [
        Config::RECOMMENDATION_TYPE_UPSELL,
        Config::RECOMMENDATION_TYPE_CROSSSELL,
    ];

    /**
     * @var array<string, bool>
     */
    private array $prefetched = [];

    /**
     * @param Config $config
     * @param TemplateFinder $templateFinder
     * @param RequestFactory $requestFactory
     * @param RequestPool $requestPool
     * @param ProfileKeyApplier $profileKeyApplier
     */
    public function __construct(
        private readonly Config $config,
        private readonly TemplateFinder $templateFinder,
        private readonly RequestFactory $requestFactory,
        private readonly RequestPool $requestPool,
        private readonly ProfileKeyApplier $profileKeyApplier
    ) {
    }

    /**
     * Queue the requests for every enabled recommendation type of the product except $currentType, which the
     * caller is about to resolve itself. Each product/type combination is only prefetched once per request.
     *
     * @param Product $product
     * @param string|null $currentType
     * @return void
     */
    public function prefetchForProduct(Product $product, ?string $currentType = null): void
    {
        if (!$this->config->isRecommendationsBatchingEnabled()) {
            return;
        }

        foreach (self::PRODUCT_TYPES as $type) {
            if ($type === $currentType) {
                continue;
            }

            $prefetchKey = $product->getId() . ':' . $type;
            if (isset($this->prefetched[$prefetchKey])) {
                continue;
            }

            $this->prefetched[$prefetchKey] = true;
            $this->queue($product, $type);
        }
    }

    /**
     * @param Product $product
     * @param string $type
     * @return void
     */
    private function queue(Product $product, string $type): void
    {
        if (!$this->config->isRecommendationsEnabled($type)) {
            return;
        }

        $templateId = $this->templateFinder->forProduct($product, $type);
        if (!$templateId) {
            return;
        }

        $request = $this->requestFactory->create();
        if (!$request instanceof ProductRequest) {
            return;
        }

        $request->setProduct($product);
        $request->setTemplate($templateId);
        $this->profileKeyApplier->apply($request);

        try {
            $this->requestPool->add($request);
        } catch (ApiException) {
            // Prefetching is speculative; the block that actually renders this type reports the failure itself.
        }
    }
}
