<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Tweakwise\Magento2Tweakwise\Api;

use Tweakwise\Magento2Tweakwise\Api\Data\AttributeSlugInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\NoSuchEntityException;

interface AttributeSlugRepositoryInterface
{
    /**
     * @param \Tweakwise\Magento2Tweakwise\Api\Data\AttributeSlugInterface $attributeSlug
     * @return \Tweakwise\Magento2Tweakwise\Api\Data\AttributeSlugInterface
     */
    public function save(AttributeSlugInterface $attributeSlug);

    /**
     * @param string $attribute
     * @return \Tweakwise\Magento2Tweakwise\Api\Data\AttributeSlugInterface
     * @throws NoSuchEntityException
     */
    public function findByAttribute(string $attribute): AttributeSlugInterface;

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $criteria
     * @return \Tweakwise\Magento2Tweakwise\Api\Data\AttributeSlugSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $criteria);

    /**
     * @param \Tweakwise\Magento2Tweakwise\Api\Data\AttributeSlugInterface $attributeSlug
     * @return bool
     */
    public function delete(AttributeSlugInterface $attributeSlug): bool;

    /**
     * @return void
     */
    public function truncateSlugTable(): void;
}
