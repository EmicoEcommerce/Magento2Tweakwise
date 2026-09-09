<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Catalog\Product;

use Emico\CodeCept\Test\Unit;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;
use Tweakwise\Magento2Tweakwise\Api\Data\VisualInterface;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\NavigationContext;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Product\Collection;
use Tweakwise\Magento2Tweakwise\Model\Client\Response\ProductNavigationResponse;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\ItemType as ClientItemType;
use Tweakwise\Magento2Tweakwise\Model\VisualFactory;

class CollectionTest extends Unit
{
    use MockeryPHPUnitIntegration;

    /**
     * @throws ReflectionException
     */
    public function testAddVisualsPreservesRawVisualItemIdForSetIdAndTwId(): void
    {
        $visualItemNo = 'banner-perfectfit';

        $visualItem = new ClientItemType([
            ClientItemType::TYPE => 'visual',
            ClientItemType::ID => $visualItemNo,
            ClientItemType::IMAGE => 'https://example.com/banner.png',
            ClientItemType::URL => 'https://example.com/banner-link',
        ]);

        $response = Mockery::mock(ProductNavigationResponse::class);
        $response->shouldReceive('getProductIds')->never();
        $response->shouldReceive('getItems')->atLeast()->once()->andReturn([$visualItem]);

        $navigationContext = Mockery::mock(NavigationContext::class);
        $navigationContext->shouldReceive('getResponse')->once()->andReturn($response);

        $visual = Mockery::mock(VisualInterface::class);
        $visual->shouldReceive('setId')->once()->with($visualItemNo)->andReturnSelf();
        $visual->shouldReceive('setData')->once()->with(ClientItemType::TWEAKWISE_ID, $visualItemNo)->andReturnSelf();
        $visual->shouldReceive('setImageUrl')->once()->with('https://example.com/banner.png')->andReturnSelf();
        $visual->shouldReceive('setUrl')->once()->with('https://example.com/banner-link')->andReturnSelf();
        $visual->shouldReceive('setVisualAttributes')->once()->with([])->andReturnSelf();

        $visualFactory = Mockery::mock(VisualFactory::class);
        $visualFactory->shouldReceive('create')->once()->andReturn($visual);

        $subject = $this->createSubject();
        $this->setProperty($subject, 'navigationContext', $navigationContext);
        $this->setProperty($subject, 'visualFactory', $visualFactory);
        $this->setProperty($subject, '_items', []);

        $addVisuals = new ReflectionMethod(Collection::class, 'addVisuals');
        $addVisuals->setAccessible(true);
        $addVisuals->invoke($subject);

        $items = $this->getProperty($subject, '_items');
        $this->assertCount(1, $items);
        $this->assertSame($visual, $items[0]);
    }

    /**
     * @throws ReflectionException
     */
    private function createSubject(): Collection
    {
        $reflection = new ReflectionClass(Collection::class);

        /** @var Collection $subject */
        $subject = $reflection->newInstanceWithoutConstructor();
        return $subject;
    }

    /**
     * @throws ReflectionException
     */
    private function setProperty(object $subject, string $propertyName, mixed $value): void
    {
        $property = $this->findProperty($subject, $propertyName);
        $property->setValue($subject, $value);
    }

    /**
     * @throws ReflectionException
     */
    private function getProperty(object $subject, string $propertyName): mixed
    {
        $property = $this->findProperty($subject, $propertyName);
        return $property->getValue($subject);
    }

    /**
     * @throws ReflectionException
     */
    private function findProperty(object $subject, string $propertyName): ReflectionProperty
    {
        $reflection = new ReflectionClass($subject);
        while ($reflection) {
            if ($reflection->hasProperty($propertyName)) {
                $property = $reflection->getProperty($propertyName);
                $property->setAccessible(true);
                return $property;
            }
            $reflection = $reflection->getParentClass();
        }

        throw new ReflectionException(sprintf('Property "%s" not found', $propertyName));
    }
}
