<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Catalog\Layer\Url\Strategy;

use Emico\CodeCept\Test\Unit;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Magento\Eav\Model\Entity\Attribute\Option;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Filter\TranslitUrl;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Tweakwise\Magento2Tweakwise\Api\AttributeSlugRepositoryInterface;
use Tweakwise\Magento2Tweakwise\Api\Data\AttributeSlugInterface;
use Tweakwise\Magento2Tweakwise\Api\Data\AttributeSlugInterfaceFactory;
use Tweakwise\Magento2Tweakwise\Api\Data\AttributeSlugSearchResultsInterface;
use Tweakwise\Magento2Tweakwise\Model\AttributeSlug;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Filter\Item;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\Strategy\FilterSlugManager;
use Tweakwise\Test\Support\UnitTester;

class FilterSlugManagerTest extends Unit
{
    use MockeryPHPUnitIntegration;

    protected UnitTester $tester;

    private TranslitUrl&MockInterface $translitUrl;
    private AttributeSlugRepositoryInterface&MockInterface $attributeSlugRepository;
    private AttributeSlugInterfaceFactory&MockInterface $attributeSlugFactory;
    private CacheInterface&MockInterface $cache;
    private StoreManagerInterface&MockInterface $storeManager;

    private FilterSlugManager $subject;

    private Json $serializer;

    protected function _before(): void
    {
        $store = Mockery::mock(StoreInterface::class);
        $store->shouldReceive('getId')->andReturn(1);

        $this->translitUrl = Mockery::mock(TranslitUrl::class);
        $this->attributeSlugRepository = Mockery::mock(AttributeSlugRepositoryInterface::class);
        $this->attributeSlugFactory = Mockery::mock(AttributeSlugInterfaceFactory::class);
        $this->cache = Mockery::mock(CacheInterface::class);
        $this->storeManager = Mockery::mock(StoreManagerInterface::class);
        $this->storeManager->shouldReceive('getStore')->andReturn($store);
        $this->serializer = new Json();

        $this->tester->mockService(TranslitUrl::class, $this->translitUrl);
        $this->tester->mockService(AttributeSlugRepositoryInterface::class, $this->attributeSlugRepository);
        $this->tester->mockService(AttributeSlugInterfaceFactory::class, $this->attributeSlugFactory);
        $this->tester->mockService(CacheInterface::class, $this->cache);
        $this->tester->mockService(StoreManagerInterface::class, $this->storeManager);
        $this->tester->mockService(Json::class, $this->serializer);

        $this->subject = $this->tester->getObjectManager()->get(FilterSlugManager::class);
    }

    public function testGetSlugForFilterItemCachesSavedSlugInMemory(): void
    {
        $this->cache
            ->shouldReceive('load')
            ->once()
            ->with('tweakwise.slug.lookup')
            ->andReturn($this->serializer->serialize([]));

        $this->cache->shouldReceive('remove')->once()->with('tweakwise.slug.lookup');

        $this->translitUrl->shouldReceive('filter')->once()->with('color')->andReturn('color');

        $attributeSlugEntity = Mockery::mock(AttributeSlug::class);
        $attributeSlugEntity->shouldReceive('setAttribute')->once()->with('color');
        $attributeSlugEntity->shouldReceive('setStoreId')->once()->with(1);
        $attributeSlugEntity->shouldReceive('setSlug')->once()->with('color');

        $this->attributeSlugFactory->shouldReceive('create')->once()->andReturn($attributeSlugEntity);

        $savedSlug = Mockery::mock(AttributeSlugInterface::class);
        $savedSlug->shouldReceive('getSlug')->twice()->andReturn('color');

        $this->attributeSlugRepository
            ->shouldReceive('save')
            ->once()
            ->with($attributeSlugEntity)
            ->andReturn($savedSlug);

        $filterAttribute = new DataObject(['title' => 'Color']);
        $filterItem = Mockery::mock(Item::class);
        $filterItem->shouldReceive('getAttribute')->twice()->andReturn($filterAttribute);

        $this->assertSame('color', $this->subject->getSlugForFilterItem($filterItem));
        $this->assertSame('color', $this->subject->getSlugForFilterItem($filterItem));
    }

    public function testCreateFilterSlugByOptionInitializesLookupTableAndUpdatesInMemoryMapping(): void
    {
        $this->cache
            ->shouldReceive('load')
            ->once()
            ->with('tweakwise.slug.lookup')
            ->andReturn($this->serializer->serialize([]));

        $this->cache->shouldReceive('remove')->once()->with('tweakwise.slug.lookup');

        $this->translitUrl->shouldReceive('filter')->times(2)->with('Blue')->andReturn('blue');

        $attributeSlugEntity = Mockery::mock(AttributeSlug::class);
        $attributeSlugEntity->shouldReceive('setAttribute')->once()->with('Blue');
        $attributeSlugEntity->shouldReceive('setStoreId')->once()->with(1);
        $attributeSlugEntity->shouldReceive('setSlug')->once()->with('blue');
        $attributeSlugEntity->shouldReceive('setData')->once()->with('attribute_code', null);

        $this->attributeSlugFactory->shouldReceive('create')->once()->andReturn($attributeSlugEntity);

        $savedSlug = Mockery::mock(AttributeSlugInterface::class);
        $savedSlug->shouldReceive('getSlug')->once()->andReturn('blue');

        $this->attributeSlugRepository
            ->shouldReceive('save')
            ->once()
            ->with($attributeSlugEntity)
            ->andReturn($savedSlug);

        $option = Mockery::mock(Option::class);
        $option->shouldReceive('offsetGet')->times(5)->with('label')->andReturn('Blue');

        $this->subject->createFilterSlugByOption($option, 1);

        $this->assertSame('blue', $this->subject->getAttributeBySlug('blue'));
    }

    public function testTruncateSlugTableReloadsLookupTableFromDatabase(): void
    {
        $this->cache
            ->shouldReceive('load')
            ->twice()
            ->with('tweakwise.slug.lookup')
            ->andReturn(
                $this->serializer->serialize([1 => ['color' => 'old-slug']]),
                false,
            );

        $this->cache->shouldReceive('remove')->once()->with('tweakwise.slug.lookup');

        $this->cache
            ->shouldReceive('save')
            ->once()
            ->with($this->serializer->serialize([1 => ['color' => 'new-slug']]), 'tweakwise.slug.lookup');

        $this->attributeSlugRepository->shouldReceive('truncateSlugTable')->once();

        $attributeSlug = Mockery::mock(AttributeSlugInterface::class);
        $attributeSlug->shouldReceive('getStoreId')->once()->andReturn(1);
        $attributeSlug->shouldReceive('getAttribute')->once()->andReturn('color');
        $attributeSlug->shouldReceive('getSlug')->once()->andReturn('new-slug');

        $searchResults = Mockery::mock(AttributeSlugSearchResultsInterface::class);
        $searchResults->shouldReceive('getItems')->once()->andReturn([$attributeSlug]);

        $this->attributeSlugRepository
            ->shouldReceive('getList')
            ->once()
            ->andReturn($searchResults);

        $this->assertSame([1 => ['color' => 'old-slug']], $this->subject->getLookupTable());

        $this->subject->truncateSlugTable();

        $this->assertSame([1 => ['color' => 'new-slug']], $this->subject->getLookupTable());
    }
}
