<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Block\Catalog\Product\ProductList;

use Tweakwise\Magento2Tweakwise\Block\Catalog\Product\ProductList\AbstractRecommendationPlugin;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Product\Recommendation\Collection;
use Tweakwise\Magento2Tweakwise\Model\Config;

class TestRecommendationPlugin extends AbstractRecommendationPlugin
{
    protected function getType()
    {
        return Config::RECOMMENDATION_TYPE_UPSELL;
    }

    public function fetchCollection(): Collection
    {
        return $this->getCollection();
    }
}
