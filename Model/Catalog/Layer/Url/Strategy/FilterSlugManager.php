<?php

/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\Strategy;

use Magento\Framework\Phrase;
use Tweakwise\Magento2Tweakwise\Api\AttributeSlugRepositoryInterface;
use Tweakwise\Magento2Tweakwise\Api\Data\AttributeSlugInterfaceFactory;
use Tweakwise\Magento2Tweakwise\Exception\UnexpectedValueException;
use Tweakwise\Magento2Tweakwise\Model\AttributeSlug;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Filter\Item;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Filter\TranslitUrl;
use Magento\Framework\Serialize\SerializerInterface;

class FilterSlugManager
{
    private const CACHE_KEY = 'tweakwise.slug.lookup';

    /**
     * @var TranslitUrl
     */
    protected $translitUrl;

    /**
     * @var AttributeSlugRepositoryInterface
     */
    protected $attributeSlugRepository;

    /**
     * @var AttributeSlugInterfaceFactory
     */
    protected $attributeSlugFactory;

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var array
     */
    protected $lookupTable;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @param TranslitUrl $translitUrl
     * @param AttributeSlugRepositoryInterface $attributeSlugRepository
     * @param AttributeSlugInterfaceFactory $attributeSlugFactory
     * @param CacheInterface $cache
     * @param SerializerInterface $serializer
     */
    public function __construct(
        TranslitUrl $translitUrl,
        AttributeSlugRepositoryInterface $attributeSlugRepository,
        AttributeSlugInterfaceFactory $attributeSlugFactory,
        CacheInterface $cache,
        SerializerInterface $serializer
    ) {
        $this->translitUrl = $translitUrl;
        $this->attributeSlugRepository = $attributeSlugRepository;
        $this->attributeSlugFactory = $attributeSlugFactory;
        $this->cache = $cache;
        $this->serializer = $serializer;
    }

    /**
     * @param Item $filterItem
     * @return string
     */
    public function getSlugForFilterItem(Item $filterItem): string
    {
        $lookupTable = $this->getLookupTable();
        $attribute = strtolower($filterItem->getAttribute()->getTitle());

        if (!empty($lookupTable[$attribute])) {
            return $lookupTable[$attribute];
        }

        $slug = $this->translitUrl->filter($attribute);

        if (empty($slug)) {
            //should never happen, but just in case we return the attribute
            return $attribute;
        }

        /** @var AttributeSlug $attributeSlugEntity */
        $attributeSlugEntity = $this->attributeSlugFactory->create();
        $attributeSlugEntity->setAttribute($attribute);
        $attributeSlugEntity->setSlug($slug);

        $this->attributeSlugRepository->save($attributeSlugEntity);
        $this->cache->remove(self::CACHE_KEY);

        return $slug;
    }

    /**
     * @param \Magento\Eav\Api\Data\AttributeOptionInterface[] $options
     * @return void
     */
    public function createFilterSlugByAttributeOptions(array $options)
    {
        foreach ($options as $option) {
            if (empty($option->getLabel()) || ctype_space((string) $option->getLabel())) {
                continue;
            }

            $this->getLookupTable();
            $optionLabel = $option->getLabel();
            if ($optionLabel instanceof Phrase) {
                $optionLabel = $optionLabel->render();
            }

            if (empty($this->translitUrl->filter($option->getLabel()))) {
                continue;
            }

            if (isset($this->lookupTable[strtolower($option->getLabel())])) {
                continue;
            }

            $attributeSlugEntity = $this->attributeSlugFactory->create();
            $attributeSlugEntity->setAttribute($option->getLabel());
            $attributeSlugEntity->setSlug($this->translitUrl->filter($option->getLabel()));

            $this->attributeSlugRepository->save($attributeSlugEntity);
            $this->cache->remove(self::CACHE_KEY);
        }
    }

    /**
     * @param string $slug
     * @return string
     * @throws UnexpectedValueException
     */
    public function getAttributeBySlug(string $slug): string
    {
        $attribute = array_search($slug, $this->getLookupTable(), false);
        if ($attribute === false) {
            // Check if slug matched the pattern for a slider filter (i.e. 80-120).
            if (preg_match('/^\d+-\d+$/', $slug)) {
                return $slug;
            }

            throw new UnexpectedValueException(sprintf('No attribute found for slug "%s"', $slug));
        }

        return $attribute;
    }

    /**
     * @return array
     */
    public function getLookupTable(): array
    {
        if ($this->lookupTable === null) {
            $this->lookupTable = $this->loadLookupTable();
        }

        return $this->lookupTable;
    }

    /**
     * @return array
     */
    protected function loadLookupTable(): array
    {
        $lookupTable = $this->cache->load(self::CACHE_KEY);
        if ($lookupTable === false) {
            $attributeSlugs = $this->attributeSlugRepository->getList(new SearchCriteria());
            $lookupTable = [];
            foreach ($attributeSlugs->getItems() as $attributeSlug) {
                $lookupTable[$attributeSlug->getAttribute()] = $attributeSlug->getSlug();
            }

            $this->cache->save($this->serializer->serialize($lookupTable), self::CACHE_KEY);
        } else {
            $lookupTable = $this->serializer->unserialize($lookupTable);
        }

        return $lookupTable;
    }
}
