<?php

/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Tweakwise\Magento2Tweakwise\Block\Catalog\Product\Widget;

use Tweakwise\Magento2Tweakwise\Block\Catalog\Product\ProductList\Featured;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Product\Recommendation\Collection;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\Recommendations\FeaturedRequest;
use Magento\Widget\Block\BlockInterface;

class FeaturedProducts extends Featured implements BlockInterface
{
    /**
     * Set default template
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('Magento_Catalog::product/list/items.phtml');
    }

    /**
     * @return bool
     */
    protected function checkRecommendationEnabled(): bool
    {
        return (bool) $this->getRuleId();
    }

    /**
     * @param FeaturedRequest $request
     */
    protected function configureRequest(FeaturedRequest $request)
    {
        $request->setTemplate($this->getRuleId());
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return 'new';
    }

    /**
     * @return Collection
     */
    public function getItems(): Collection
    {
        return $this->_getProductCollection();
    }
}
