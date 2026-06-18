<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model;

use Emico\CodeCept\Test\Unit;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Tweakwise\Magento2Tweakwise\Api\Data\AttributeSlugInterface;
use Tweakwise\Magento2Tweakwise\Api\Data\AttributeSlugInterfaceFactory;
use Tweakwise\Magento2Tweakwise\Api\Data\AttributeSlugSearchResultsInterfaceFactory;
use Tweakwise\Magento2Tweakwise\Model\AttributeSlug;
use Tweakwise\Magento2Tweakwise\Model\AttributeSlugRepository;
use Tweakwise\Magento2Tweakwise\Model\ResourceModel\AttributeSlug as AttributeSlugResource;
use Tweakwise\Magento2Tweakwise\Model\ResourceModel\AttributeSlug\Collection;
use Tweakwise\Magento2Tweakwise\Model\ResourceModel\AttributeSlug\CollectionFactory;
use Tweakwise\Test\Support\UnitTester;

class AttributeSlugRepositoryTest extends Unit
{
    protected UnitTester $tester;

    /**
     * @var AttributeSlugResource&MockObject
     */
    private AttributeSlugResource|MockObject $resource;

    /**
     * @var AttributeSlugInterfaceFactory&MockObject
     */
    private AttributeSlugInterfaceFactory|MockObject $entityFactory;

    /**
     * @var CollectionFactory&MockObject
     */
    private CollectionFactory|MockObject $collectionFactory;

    /**
     * @var AttributeSlugSearchResultsInterfaceFactory&MockObject
     */
    private AttributeSlugSearchResultsInterfaceFactory|MockObject $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface&MockObject
     */
    private CollectionProcessorInterface|MockObject $collectionProcessor;

    private AttributeSlugRepository $subject;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->resource = $this->createMock(AttributeSlugResource::class);
        $this->entityFactory = $this->createMock(AttributeSlugInterfaceFactory::class);
        $this->collectionFactory = $this->createMock(CollectionFactory::class);
        $this->searchResultsFactory = $this->createMock(AttributeSlugSearchResultsInterfaceFactory::class);
        $this->collectionProcessor = $this->createMock(CollectionProcessorInterface::class);

        $this->subject = new AttributeSlugRepository(
            $this->resource,
            $this->entityFactory,
            $this->collectionFactory,
            $this->searchResultsFactory,
            $this->collectionProcessor,
        );
    }

    /**
     * @return void
     */
    public function testSaveReusesExistingPrimaryKeyAndSkipsWriteWhenSlugIsUnchanged(): void
    {
        $attributeSlug = $this->createMock(AttributeSlug::class);
        $attributeSlug->method('getSlug')->willReturn('color');
        $attributeSlug->method('getStoreId')->willReturn(1);
        $attributeSlug->method('getAttribute')->willReturn('color');
        $attributeSlug->expects($this->once())->method('setData')->with('id', 25);

        $existingByAttributeAndStore = $this->createMock(AttributeSlug::class);
        $existingByAttributeAndStore->method('getData')->with('id')->willReturn(25);
        $existingByAttributeAndStore->method('getSlug')->willReturn('color');

        $existingCollection = $this->createMock(Collection::class);
        $existingCollection->method('addFieldToFilter')->willReturnSelf();
        $existingCollection->method('getSize')->willReturn(1);
        $existingCollection->method('getFirstItem')->willReturn($existingByAttributeAndStore);

        $this->collectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($existingCollection);

        $this->resource->expects($this->never())->method('save');

        $result = $this->subject->save($attributeSlug);

        $this->assertSame($attributeSlug, $result);
    }

    /**
     * @return void
     */
    public function testSaveReusesExistingPrimaryKeyAndPersistsUpdatedSlug(): void
    {
        $attributeSlug = $this->createMock(AttributeSlug::class);
        $attributeSlug->method('getSlug')->willReturn('color');
        $attributeSlug->method('getStoreId')->willReturn(1);
        $attributeSlug->method('getAttribute')->willReturn('color');
        $attributeSlug->expects($this->once())->method('setData')->with('id', 25);
        $attributeSlug->expects($this->once())->method('setSlug')->with('color');

        $existingByAttributeAndStore = $this->createMock(AttributeSlug::class);
        $existingByAttributeAndStore->method('getData')->with('id')->willReturn(25);
        $existingByAttributeAndStore->method('getSlug')->willReturn('old-color');

        $existingCollection = $this->createMock(Collection::class);
        $existingCollection->method('addFieldToFilter')->willReturnSelf();
        $existingCollection->method('getSize')->willReturn(1);
        $existingCollection->method('getFirstItem')->willReturn($existingByAttributeAndStore);

        $emptyCollection = $this->createMock(Collection::class);
        $emptyCollection->method('addFieldToFilter')->willReturnSelf();
        $emptyCollection->method('getSize')->willReturn(0);

        $this->collectionFactory->expects($this->exactly(2))
            ->method('create')
            ->willReturnOnConsecutiveCalls($existingCollection, $emptyCollection);

        $this->resource->expects($this->once())
            ->method('save')
            ->with($attributeSlug);

        $result = $this->subject->save($attributeSlug);

        $this->assertSame($attributeSlug, $result);
    }
}
