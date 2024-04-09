<?php

/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Model\Client\Request\Recommendations;

use Tweakwise\Magento2Tweakwise\Exception\ApiException;
use Magento\Catalog\Model\Product;

class ProductRequest extends FeaturedRequest
{
    /**
     * @var Product
     */
    protected $product;

    /**
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param Product $product
     * @return $this
     */
    public function setProduct(Product $product)
    {
        $this->product = $product;
        return $this;
    }

    public function getPath()
    {
        if (is_int($this->templateId)) {
            return 'recommendations/product';
        }

        return 'recommendations/grouped';
    }

    /**
     * @return string
     * @throws ApiException
     */
    public function getPathSuffix()
    {
        if (!$this->product) {
            throw new ApiException('Featured products without product was requested.');
        }

        $productTweakwiseId = $this->helper->getTweakwiseId($this->product->getStoreId(), $this->product->getId());
        if (is_int($this->templateId)) {
            return parent::getPathSuffix() . '/' . $productTweakwiseId;
        }

        return '/' . $productTweakwiseId . parent::getPathSuffix();
    }
}
