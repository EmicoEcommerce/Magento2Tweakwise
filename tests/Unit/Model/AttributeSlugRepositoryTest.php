<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model;

use Emico\CodeCept\Test\Unit;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
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
    use MockeryPHPUnitIntegration;

    protected UnitTester $tester;

    /**
     * @var AttributeSlugResource&MockInterface
     */
    private AttributeSlugResource&MockInterface $resource;

    /**
     * @var AttributeSlugInterfaceFactory&MockInterface
     */
    private AttributeSlugInterfaceFactory&MockInterface $entityFactory;

    /**
     * @var CollectionFactory&MockInterface
     */
    private CollectionFactory&MockInterface $collectionFactory;

    /**
     * @var AttributeSlugSearchResultsInterfaceFactory&MockInterface
     */
    private AttributeSlugSearchResultsInterfaceFactory&MockInterface $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface&MockInterface
     */
    private CollectionProcessorInterface&MockInterface $collectionProcessor;

    private AttributeSlugRepository $subject;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->resource = Mockery::mock(AttributeSlugResource::class);
        $this->entityFactory = Mockery::mock(AttributeSlugInterfaceFactory::class);
        $this->collectionFactory = Mockery::mock(CollectionFactory::class);
        $this->searchResultsFactory = Mockery::mock(AttributeSlugSearchResultsInterfaceFactory::class);
        $this->collectionProcessor = Mockery::mock(CollectionProcessorInterface::class);

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
        $attributeSlug = Mockery::mock(AttributeSlug::class);
        $attributeSlug->shouldReceive('getSlug')->once()->andReturn('color');
        $attributeSlug->shouldReceive('getStoreId')->once()->andReturn(1);
        $attributeSlug->shouldReceive('getAttribute')->once()->andReturn('color');
        $attributeSlug->shouldReceive('setData')->once()->with('id', 25);

        $existingByAttributeAndStore = Mockery::mock(AttributeSlug::class);
        $existingByAttributeAndStore->shouldReceive('getData')->once()->with('id')->andReturn(25);
        $existingByAttributeAndStore->shouldReceive('getSlug')->once()->andReturn('color');

        $existingCollection = Mockery::mock(Collection::class);
        $existingCollection->shouldReceive('addFieldToFilter')->twice()->andReturnSelf();
        $existingCollection->shouldReceive('getSize')->once()->andReturn(1);
        $existingCollection->shouldReceive('getFirstItem')->once()->andReturn($existingByAttributeAndStore);

        $this->collectionFactory->shouldReceive('create')->once()->andReturn($existingCollection);

        $this->resource->shouldNotReceive('save');

        $result = $this->subject->save($attributeSlug);

        $this->assertSame($attributeSlug, $result);
    }

    /**
     * @return void
     */
    public function testSaveReusesExistingPrimaryKeyAndPersistsUpdatedSlug(): void
    {
        $attributeSlug = Mockery::mock(AttributeSlug::class);
        $attributeSlug->shouldReceive('getSlug')->once()->andReturn('color');
        $attributeSlug->shouldReceive('getStoreId')->once()->andReturn(1);
        $attributeSlug->shouldReceive('getAttribute')->once()->andReturn('color');
        $attributeSlug->shouldReceive('setData')->once()->with('id', 25);
        $attributeSlug->shouldReceive('setSlug')->once()->with('color');

        $existingByAttributeAndStore = Mockery::mock(AttributeSlug::class);
        $existingByAttributeAndStore->shouldReceive('getData')->once()->with('id')->andReturn(25);
        $existingByAttributeAndStore->shouldReceive('getSlug')->once()->andReturn('old-color');

        $existingCollection = Mockery::mock(Collection::class);
        $existingCollection->shouldReceive('addFieldToFilter')->twice()->andReturnSelf();
        $existingCollection->shouldReceive('getSize')->once()->andReturn(1);
        $existingCollection->shouldReceive('getFirstItem')->once()->andReturn($existingByAttributeAndStore);

        $emptyCollection = Mockery::mock(Collection::class);
        $emptyCollection->shouldReceive('addFieldToFilter')->twice()->andReturnSelf();
        $emptyCollection->shouldReceive('getSize')->once()->andReturn(0);

        $this->collectionFactory
            ->shouldReceive('create')
            ->twice()
            ->andReturn($existingCollection, $emptyCollection);

        $this->resource->shouldReceive('save')->once()->with($attributeSlug);

        $result = $this->subject->save($attributeSlug);

        $this->assertSame($attributeSlug, $result);
    }
}
