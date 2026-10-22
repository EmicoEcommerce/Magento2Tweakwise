<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Catalog\Product\Recommendation;

use Emico\CodeCept\Test\Unit;
use Magento\Catalog\Model\Product;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Product\Recommendation\Collection;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Product\VisualBuilder;
use Tweakwise\Magento2Tweakwise\Model\Client\Response\RecommendationsResponse;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\ItemType as ClientItemType;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2Tweakwise\Model\Visual;

class CollectionTest extends Unit
{
    use MockeryPHPUnitIntegration;

    /**
     * @throws ReflectionException
     */
    public function testAddVisualsPlacesVisualAtItsPositionInTheResponse(): void
    {
        $products = $this->createProducts([101, 102, 103]);
        $visual = $this->createVisual('visual-a');

        $items = $this->addVisuals(
            [
                $this->createVisualItem('visual-a'),
                $this->createProductItem(101),
                $this->createProductItem(102),
                $this->createProductItem(103),
            ],
            $products,
            ['visual-a' => $visual]
        );

        $this->assertSame(
            [$visual, $products[101], $products[102], $products[103]],
            array_values($items)
        );
    }

    /**
     * @throws ReflectionException
     */
    public function testAddVisualsSupportsMultipleVisuals(): void
    {
        $products = $this->createProducts([101, 102]);
        $first = $this->createVisual('visual-a');
        $second = $this->createVisual('visual-b');

        $items = $this->addVisuals(
            [
                $this->createProductItem(101),
                $this->createVisualItem('visual-a'),
                $this->createProductItem(102),
                $this->createVisualItem('visual-b'),
            ],
            $products,
            ['visual-a' => $first, 'visual-b' => $second]
        );

        $this->assertSame(
            [$products[101], $first, $products[102], $second],
            array_values($items)
        );
    }

    /**
     * @throws ReflectionException
     */
    public function testAddVisualsKeepsProductIdKeys(): void
    {
        $products = $this->createProducts([101, 102]);

        $items = $this->addVisuals(
            [
                $this->createVisualItem('visual-a'),
                $this->createProductItem(101),
                $this->createProductItem(102),
            ],
            $products,
            ['visual-a' => $this->createVisual('visual-a')]
        );

        $this->assertSame($products[101], $items[101]);
        $this->assertSame($products[102], $items[102]);
    }

    /**
     * @throws ReflectionException
     */
    public function testAddVisualsDoesNothingWhenVisualsAreDisabled(): void
    {
        $products = $this->createProducts([101]);

        $items = $this->addVisuals(
            [$this->createVisualItem('visual-a'), $this->createProductItem(101)],
            $products,
            [],
            false
        );

        $this->assertSame($products, $items);
    }

    /**
     * @throws ReflectionException
     */
    public function testAddVisualsDoesNothingWhenResponseHasNoVisuals(): void
    {
        $products = $this->createProducts([101, 102]);

        $items = $this->addVisuals(
            [$this->createProductItem(101), $this->createProductItem(102)],
            $products,
            []
        );

        $this->assertSame($products, $items);
    }

    /**
     * In grouped products mode every product has two response items (group and simple variant), but only one
     * collection item. The visual must be placed after the products that precede it, not after the response items.
     *
     * @throws ReflectionException
     */
    public function testAddVisualsCountsProductsInsteadOfResponseItemsInGroupedMode(): void
    {
        $products = $this->createProducts([101, 102, 103]);
        $visual = $this->createVisual('visual-a');

        $items = $this->addVisuals(
            [
                $this->createProductItem(101),
                $this->createProductItem(101),
                $this->createProductItem(102),
                $this->createProductItem(102),
                $this->createVisualItem('visual-a'),
                $this->createProductItem(103),
                $this->createProductItem(103),
            ],
            $products,
            ['visual-a' => $visual]
        );

        $this->assertSame(
            [$products[101], $products[102], $visual, $products[103]],
            array_values($items)
        );
    }

    /**
     * @throws ReflectionException
     */
    public function testAddVisualsKeepsVisualsWithoutId(): void
    {
        $products = $this->createProducts([101, 102]);
        $first = $this->createVisual('');
        $second = $this->createVisual('');

        $items = $this->addVisuals(
            [
                $this->createVisualItem(''),
                $this->createProductItem(101),
                $this->createVisualItem(''),
                $this->createProductItem(102),
            ],
            $products,
            [],
            true,
            [$first, $second]
        );

        $this->assertSame(
            [$first, $products[101], $second, $products[102]],
            array_values($items)
        );
    }

    /**
     * @param ClientItemType[] $responseItems
     * @param Product[] $products Loaded products, keyed by product id
     * @param Visual[] $visuals Visuals the builder returns, keyed by visual item id
     * @param bool $visualsEnabled
     * @param Visual[] $visualQueue Visuals the builder returns in order, for visuals that share an id
     * @return array
     * @throws ReflectionException
     */
    private function addVisuals(
        array $responseItems,
        array $products,
        array $visuals,
        bool $visualsEnabled = true,
        array $visualQueue = []
    ): array {
        $response = Mockery::mock(RecommendationsResponse::class);
        $response->shouldReceive('getItems')->andReturn($responseItems);
        $response->shouldReceive('getProductIds')->andReturn(array_map(
            fn (ClientItemType $item) => $item->getType() === 'visual' ? 0 : (int) $item->getValue(ClientItemType::ID),
            $responseItems
        ));

        $config = Mockery::mock(Config::class);
        $config->shouldReceive('isVisualsEnabled')->andReturn($visualsEnabled);

        $visualBuilder = Mockery::mock(VisualBuilder::class);
        if ($visualQueue) {
            $visualBuilder->shouldReceive('build')->andReturnValues($visualQueue);
        } else {
            $visualBuilder->shouldReceive('build')->andReturnUsing(
                fn (ClientItemType $item) => $visuals[$item->getValue(ClientItemType::ID)]
            );
        }

        $reflection = new ReflectionClass(Collection::class);
        /** @var Collection $subject */
        $subject = $reflection->newInstanceWithoutConstructor();
        $this->setProperty($subject, 'response', $response);
        $this->setProperty($subject, 'config', $config);
        $this->setProperty($subject, 'visualBuilder', $visualBuilder);
        $this->setProperty($subject, '_items', $products);

        $method = new ReflectionMethod(Collection::class, 'addVisuals');
        $method->setAccessible(true);
        $method->invoke($subject);

        return $this->getProperty($subject, '_items');
    }

    /**
     * @param int[] $ids
     * @return Product[]
     */
    private function createProducts(array $ids): array
    {
        $products = [];
        foreach ($ids as $id) {
            $products[$id] = Mockery::mock(Product::class);
        }

        return $products;
    }

    private function createVisual(string $id): Visual
    {
        $visual = Mockery::mock(Visual::class);
        $visual->shouldReceive('getId')->andReturn($id);

        return $visual;
    }

    private function createVisualItem(string $id): ClientItemType
    {
        return new ClientItemType([
            ClientItemType::TYPE => 'visual',
            ClientItemType::ID => $id,
        ]);
    }

    private function createProductItem(int $id): ClientItemType
    {
        return new ClientItemType([
            ClientItemType::TYPE => 'product',
            ClientItemType::ID => $id,
        ]);
    }

    /**
     * @throws ReflectionException
     */
    private function setProperty(object $subject, string $name, mixed $value): void
    {
        $property = $this->findProperty($subject, $name);
        $property->setValue($subject, $value);
    }

    /**
     * @throws ReflectionException
     */
    private function getProperty(object $subject, string $name): mixed
    {
        return $this->findProperty($subject, $name)->getValue($subject);
    }

    /**
     * @throws ReflectionException
     */
    private function findProperty(object $subject, string $name): ReflectionProperty
    {
        $reflection = new ReflectionClass($subject);
        while ($reflection) {
            if ($reflection->hasProperty($name)) {
                $property = $reflection->getProperty($name);
                $property->setAccessible(true);
                return $property;
            }
            $reflection = $reflection->getParentClass();
        }

        throw new ReflectionException(sprintf('Property "%s" not found', $name));
    }
}
