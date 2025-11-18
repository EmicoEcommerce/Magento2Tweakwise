<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Model\Client\Request\Recommendations;

use Tweakwise\Magento2Tweakwise\Exception\ApiException;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

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

        if ($this->config->isGroupedProductsEnabled() && $product->getTypeId() === Configurable::TYPE_CODE) {
            $this->product = $this->getSimpleProduct($product);
        }

        return $this;
    }

    public function getPath()
    {
        // @phpstan-ignore-next-line
        if (is_int($this->templateId)) {
            return 'recommendations/product';
        }

        // @phpstan-ignore-next-line
        return 'recommendations/grouped';
    }

    /**
     * @return string
     * @throws ApiException
     */
    public function getPathSuffix()
    {
        // @phpstan-ignore-next-line
        if (!$this->product) {
            throw new ApiException('Featured products without product was requested.');
        }

        $productTweakwiseId = $this->helper->getTweakwiseId($this->product->getStoreId(), $this->product->getId());
        // @phpstan-ignore-next-line
        if (is_int($this->templateId)) {
            return parent::getPathSuffix() . '/' . $productTweakwiseId;
        }

        // @phpstan-ignore-next-line
        return '/' . $productTweakwiseId . parent::getPathSuffix();
    }

    /**
     * @param Product $product
     * @return Product
     */
    private function getSimpleProduct(Product $product): Product
    {
        // @phpstan-ignore-next-line
        $children = $product->getTypeInstance()->getUsedProducts($product);
        foreach ($children as $child) {
            if ($child->isSaleable() && $child->getTypeId() === Type::TYPE_SIMPLE) {
                return $child;
            }
        }
        return $product;
    }
}
