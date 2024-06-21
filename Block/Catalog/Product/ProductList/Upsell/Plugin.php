<?php

/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Block\Catalog\Product\ProductList\Upsell;

use Closure;
use Tweakwise\Magento2Tweakwise\Block\Catalog\Product\ProductList\AbstractRecommendationPlugin;
use Tweakwise\Magento2Tweakwise\Exception\ApiException;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Product\Recommendation\Collection;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Magento\Catalog\Block\Product\ProductList\Upsell;

class Plugin extends AbstractRecommendationPlugin
{
    /**
     * @return string
     */
    protected function getType()
    {
        return Config::RECOMMENDATION_TYPE_UPSELL;
    }

    /**
     * @param Upsell $subject
     * @param Closure $proceed
     * @return Collection
     */
    public function aroundGetItemCollection(Upsell $subject, Closure $proceed)
    {
        // TODO: TJEU CACHING IN TEMPLATES INBOUWEN
        if (!$this->config->isRecommendationsEnabled(Config::RECOMMENDATION_TYPE_UPSELL)) {
            return $proceed();
        }

        if (!$this->templateFinder->forProduct($subject->getProduct(), $this->getType())) {
            return $proceed();
        }

        try {
            return $this->getCollection();
        } catch (ApiException $e) {
            return $proceed();
        }
    }

    /**
     * @param Upsell $subject
     * @param Closure $proceed
     * @return int
     */
    public function aroundGetItemLimit(Upsell $subject, Closure $proceed, $type = '')
    {
        if (!$this->config->isRecommendationsEnabled(Config::RECOMMENDATION_TYPE_UPSELL)) {
            return $proceed($type);
        }

        try {
            return $this->getCollection()->count();
        } catch (ApiException $e) {
            return $proceed($type);
        }
    }
}
