<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Catalog\Layer\Url\Strategy;

use Emico\CodeCept\Test\Unit;
use Magento\Eav\Model\Entity\Attribute\Option;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Filter\TranslitUrl;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Tweakwise\Magento2Tweakwise\Api\AttributeSlugRepositoryInterface;
use Tweakwise\Magento2Tweakwise\Api\Data\AttributeSlugInterface;
use Tweakwise\Magento2Tweakwise\Api\Data\AttributeSlugInterfaceFactory;
use Tweakwise\Magento2Tweakwise\Api\Data\AttributeSlugSearchResultsInterface;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Filter\Item;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\Strategy\FilterSlugManager;
use Tweakwise\Test\Support\UnitTester;

class FilterSlugManagerTest extends Unit
{
    protected UnitTester $tester;

    /**
     * @var TranslitUrl&MockObject
     */
    private TranslitUrl|MockObject $translitUrl;

    /**
     * @var AttributeSlugRepositoryInterface&MockObject
     */
    private AttributeSlugRepositoryInterface|MockObject $attributeSlugRepository;

    /**
     * @var AttributeSlugInterfaceFactory&MockObject
     */
    private AttributeSlugInterfaceFactory|MockObject $attributeSlugFactory;

    /**
     * @var CacheInterface&MockObject
     */
    private CacheInterface|MockObject $cache;

    /**
     * @var StoreManagerInterface&MockObject
     */
    private StoreManagerInterface|MockObject $storeManager;

    private FilterSlugManager $subject;

    private Json $serializer;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn(1);

        $this->translitUrl = $this->createMock(TranslitUrl::class);
        $this->attributeSlugRepository = $this->createMock(AttributeSlugRepositoryInterface::class);
        $this->attributeSlugFactory = $this->createMock(AttributeSlugInterfaceFactory::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->storeManager->method('getStore')->willReturn($store);
        $this->serializer = new Json();

        $this->subject = new FilterSlugManager(
            $this->translitUrl,
            $this->attributeSlugRepository,
            $this->attributeSlugFactory,
            $this->cache,
            $this->serializer,
            $this->storeManager,
        );
    }

    /**
     * @return void
     */
    public function testGetSlugForFilterItemCachesSavedSlugInMemory(): void
    {
        $this->cache->expects($this->once())
            ->method('load')
            ->with('tweakwise.slug.lookup')
            ->willReturn($this->serializer->serialize([]));

        $this->cache->expects($this->once())
            ->method('remove')
            ->with('tweakwise.slug.lookup');

        $this->translitUrl->method('filter')->with('color')->willReturn('color');

        $attributeSlugEntity = $this->createMock(AttributeSlugInterface::class);
        $attributeSlugEntity->expects($this->once())->method('setAttribute')->with('color');
        $attributeSlugEntity->expects($this->once())->method('setStoreId')->with(1);
        $attributeSlugEntity->expects($this->once())->method('setSlug')->with('color');

        $this->attributeSlugFactory->expects($this->once())
            ->method('create')
            ->willReturn($attributeSlugEntity);

        $savedSlug = $this->createMock(AttributeSlugInterface::class);
        $savedSlug->method('getSlug')->willReturn('color');

        $this->attributeSlugRepository->expects($this->once())
            ->method('save')
            ->with($attributeSlugEntity)
            ->willReturn($savedSlug);

        $filterAttribute = new DataObject(['title' => 'Color']);
        $filterItem = $this->createMock(Item::class);
        $filterItem->method('getAttribute')->willReturn($filterAttribute);

        $this->assertSame('color', $this->subject->getSlugForFilterItem($filterItem));
        $this->assertSame('color', $this->subject->getSlugForFilterItem($filterItem));
    }

    /**
     * @return void
     */
    public function testCreateFilterSlugByOptionInitializesLookupTableAndUpdatesInMemoryMapping(): void
    {
        $this->cache->expects($this->once())
            ->method('load')
            ->with('tweakwise.slug.lookup')
            ->willReturn($this->serializer->serialize([]));

        $this->cache->expects($this->once())
            ->method('remove')
            ->with('tweakwise.slug.lookup');

        $this->translitUrl->method('filter')->with('Blue')->willReturn('blue');

        $attributeSlugEntity = $this->createMock(AttributeSlugInterface::class);
        $attributeSlugEntity->expects($this->once())->method('setAttribute')->with('Blue');
        $attributeSlugEntity->expects($this->once())->method('setStoreId')->with(1);
        $attributeSlugEntity->expects($this->once())->method('setSlug')->with('blue');
        $attributeSlugEntity->expects($this->once())->method('setData')->with('attribute_code', null);

        $this->attributeSlugFactory->expects($this->once())
            ->method('create')
            ->willReturn($attributeSlugEntity);

        $savedSlug = $this->createMock(AttributeSlugInterface::class);
        $savedSlug->method('getSlug')->willReturn('blue');

        $this->attributeSlugRepository->expects($this->once())
            ->method('save')
            ->with($attributeSlugEntity)
            ->willReturn($savedSlug);

        $option = new Option();
        $option->setData('label', 'Blue');

        $this->subject->createFilterSlugByOption($option, 1);

        $this->assertSame('blue', $this->subject->getAttributeBySlug('blue'));
    }

    /**
     * @return void
     */
    public function testTruncateSlugTableReloadsLookupTableFromDatabase(): void
    {
        $this->cache->expects($this->exactly(2))
            ->method('load')
            ->with('tweakwise.slug.lookup')
            ->willReturnOnConsecutiveCalls(
                $this->serializer->serialize([1 => ['color' => 'old-slug']]),
                false,
            );

        $this->cache->expects($this->once())
            ->method('remove')
            ->with('tweakwise.slug.lookup');

        $this->cache->expects($this->once())
            ->method('save')
            ->with($this->serializer->serialize([1 => ['color' => 'new-slug']]), 'tweakwise.slug.lookup');

        $this->attributeSlugRepository->expects($this->once())
            ->method('truncateSlugTable');

        $attributeSlug = $this->createMock(AttributeSlugInterface::class);
        $attributeSlug->method('getStoreId')->willReturn(1);
        $attributeSlug->method('getAttribute')->willReturn('color');
        $attributeSlug->method('getSlug')->willReturn('new-slug');

        $searchResults = $this->createMock(AttributeSlugSearchResultsInterface::class);
        $searchResults->method('getItems')->willReturn([$attributeSlug]);

        $this->attributeSlugRepository->expects($this->once())
            ->method('getList')
            ->willReturn($searchResults);

        $this->assertSame([1 => ['color' => 'old-slug']], $this->subject->getLookupTable());

        $this->subject->truncateSlugTable();

        $this->assertSame([1 => ['color' => 'new-slug']], $this->subject->getLookupTable());
    }
}

