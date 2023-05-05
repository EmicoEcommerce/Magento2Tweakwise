<?php

namespace Tweakwise\Magento2Tweakwise\Observer;

use Tweakwise\Magento2Tweakwise\Api\AttributeSlugRepositoryInterface;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Filter;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\Strategy\FilterSlugManager;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class CreateTweakwiseSlugsAfterSaveAttribute implements ObserverInterface
{
    /**
     * @var FilterSlugManager
     */
    protected FilterSlugManager $filterSlugManager;

    /**
     * @param FilterSlugManager $filterSlugManager
     */
    public function __construct(
        FilterSlugManager $filterSlugManager
    )
    {
        $this->filterSlugManager = $filterSlugManager;
    }

    public function execute(Observer $observer)
    {
        $attribute = $observer->getEvent()->getAttribute();
        $attribute->setStoreId(0);

        $this->filterSlugManager
            ->createFilterSlugByAttributeOptions($attribute->getOptions())
        ;
    }
}
