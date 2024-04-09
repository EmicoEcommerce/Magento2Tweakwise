<?php

namespace Tweakwise\Magento2Tweakwise\Model\Swatches;

use Magento\Catalog\Api\Data\ProductInterface as Product;
use Magento\Swatches\Helper\Data;

class Plugin
{
    /**
     * @param Data $subject
     * @param callable $proceed
     * @param Product $parentProduct
     * @param array $attributes
     * @return bool|Product
     */
    public function aroundLoadVariationByFallback(
        Data $subject,
        callable $proceed,
        Product $parentProduct,
        array $attributes
    ) {
        $hasArrayValues = false;
        foreach ($attributes as $attribute) {
            if (\is_array($attribute)) {
                $hasArrayValues = true;
            }
        }

        if (!$hasArrayValues) {
            return $proceed($parentProduct, $attributes);
        }

        $newAttributes = [];
        foreach ($attributes as $key => $attribute) {
            if (\is_array($attribute)) {
                $attribute = end($attribute);
            }

            $newAttributes[$key] = $attribute;
        }

        return $proceed($parentProduct, $newAttributes);
    }
}
