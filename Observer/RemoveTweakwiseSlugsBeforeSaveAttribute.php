<?php

namespace Tweakwise\Magento2Tweakwise\Observer;

use Tweakwise\Magento2Tweakwise\Api\AttributeSlugRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class RemoveTweakwiseSlugsBeforeSaveAttribute implements ObserverInterface
{
    /**
     * @var AttributeSlugRepositoryInterface
     */
    protected AttributeSlugRepositoryInterface $attributeSlugRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @param AttributeSlugRepositoryInterface $attributeSlugRepository
     */
    public function __construct(
        AttributeSlugRepositoryInterface $attributeSlugRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->attributeSlugRepository = $attributeSlugRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @param Observer $observer
     * phpcs:disable Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
     * phpcs:disable Generic.CodeAnalysis.EmptyStatement.DetectedCatch
     * @SuppressWarnings(PHPMD.EmptyCatchBlock)
     */
    public function execute(Observer $observer)
    {
        foreach ($observer->getEvent()->getAttribute()->getOptions() as $option) {
            try {
                if (empty($option->getLabel()) || ctype_space((string) $option->getLabel())) {
                    continue;
                }

                $this->attributeSlugRepository
                    ->delete($this->attributeSlugRepository->findByAttribute($option->getLabel()))
                ;
            } catch (NoSuchEntityException $exception) {
            }
        }
    }
}
