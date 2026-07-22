<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Tweakwise\Magento2Tweakwise\Model\Client\Response;

interface AutocompleteProductResponseInterface
{
    /**
     * @return int[]
     */
    public function getProductIds();
}
