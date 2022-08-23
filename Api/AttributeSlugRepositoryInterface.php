<?php

namespace Tweakwise\Magento2Tweakwise\Api;

use Tweakwise\Magento2Tweakwise\Api\Data\AttributeSlugInterface;
use Tweakwise\Magento2Tweakwise\Api\Data\AttributeSlugSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\NoSuchEntityException;

interface AttributeSlugRepositoryInterface
{
    /**
     * @param AttributeSlugInterface $attributeSlug
     */
    public function save(AttributeSlugInterface $attributeSlug);

    /**
     * @param string $attribute
     * @return AttributeSlugInterface
     * @throws NoSuchEntityException
     */
    public function findByAttribute(string $attribute): AttributeSlugInterface;

    /**
     * @param SearchCriteriaInterface $criteria
     * @return AttributeSlugSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $criteria);

    /**
     * @param AttributeSlugInterface $attributeSlug
     * @return bool
     */
    public function delete(AttributeSlugInterface $attributeSlug): bool;
}
