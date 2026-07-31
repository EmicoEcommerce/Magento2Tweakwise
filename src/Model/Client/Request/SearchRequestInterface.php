<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Tweakwise\Magento2Tweakwise\Model\Client\Request;

interface SearchRequestInterface
{
    /**
     * @param string $query
     */
    public function setSearch(string $query): void;
}
