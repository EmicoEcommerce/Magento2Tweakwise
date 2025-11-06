<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\Strategy;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute\Interceptor;
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
use Magento\Store\Model\StoreManagerInterface;

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
        SerializerInterface $serializer,
        protected readonly StoreManagerInterface $storeManager
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

        if (!empty($lookupTable[$this->storeManager->getStore()->getId()][$attribute])) {
            return $lookupTable[$this->storeManager->getStore()->getId()][$attribute];
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
        $attributeSlugEntity->setStoreId($this->storeManager->getStore()->getId());

        $savedSlug = $this->attributeSlugRepository->save($attributeSlugEntity);
        $slug = $savedSlug->getSlug();
        $this->cache->remove(self::CACHE_KEY);

        return $slug;
    }

    /**
     * @param \Magento\Eav\Api\Data\AttributeOptionInterface[] $options
     * @return void
     */
    public function createFilterSlugByAttributeOptions(Interceptor $options)
    {
        $allTranslations = $options->toArray();
        if (!isset($allTranslations['option']['value'])) {
            return;
        }
        foreach ($allTranslations['option']['value'] as $optionTranslations) {
            foreach ($optionTranslations as $storeId => $optionLabel) {
                if (empty($optionLabel) || ctype_space((string)$optionLabel)) {
                    continue;
                }

                $this->getLookupTable();
                // @phpstan-ignore-next-line
                if ($optionLabel instanceof Phrase) {
                    $optionLabel = $optionLabel->render();
                }

                if (empty($this->translitUrl->filter($optionLabel))) {
                    continue;
                }

                if (isset($this->lookupTable[strtolower($optionLabel)])) {
                    continue;
                }

                $attributeSlugEntity = $this->attributeSlugFactory->create();
                // @phpstan-ignore-next-line
                $attributeSlugEntity->setAttribute($optionLabel);
                $attributeSlugEntity->setStoreId((int)$storeId);
                $attributeSlugEntity->setSlug($this->translitUrl->filter($optionLabel));

                $this->attributeSlugRepository->save($attributeSlugEntity);
                $this->cache->remove(self::CACHE_KEY);
            }
        }
    }

    /**
     * @param string $slug
     * @return string
     * @throws UnexpectedValueException
     */
    public function getAttributeBySlug(string $slug): string
    {
        $lookupTable = $this->getLookupTable();
        // phpcs:disable SlevomatCodingStandard.Functions.StrictCall.NonStrictComparison
        $attribute = array_search($slug, $lookupTable[$this->storeManager->getStore()->getId()], false);

        //fallback
        if ($attribute === false) {
            $attribute = array_search($slug, $lookupTable[0], false);
        }

        if ($attribute === false) {
            // Check if slug matched the pattern for a slider filter (i.e. 80-120).
            if (preg_match('/^\d+-\d+$/', $slug)) {
                return $slug;
            }

            throw new UnexpectedValueException(sprintf('No attribute found for slug "%s"', $slug));
        }

        // @phpstan-ignore-next-line
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
        // @phpstan-ignore-next-line
        if ($lookupTable === false) {
            $attributeSlugs = $this->attributeSlugRepository->getList(new SearchCriteria());
            $lookupTable = [];
            foreach ($attributeSlugs->getItems() as $attributeSlug) {
                $lookupTable[$attributeSlug->getStoreId()][$attributeSlug->getAttribute()] = $attributeSlug->getSlug();
            }

            $this->cache->save($this->serializer->serialize($lookupTable), self::CACHE_KEY);
        } else {
            $lookupTable = $this->serializer->unserialize($lookupTable);
        }

        // @phpstan-ignore-next-line
        return $lookupTable;
    }
}
