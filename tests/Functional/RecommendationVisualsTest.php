<?php

declare(strict_types=1);

namespace Tweakwise\Test\Functional;

use Closure;
use GuzzleHttp\Psr7\Request as HttpRequest;
use GuzzleHttp\Psr7\Response as HttpResponse;
use Emico\CodeCept\Models\Fixtures\ProductFixture;
use Emico\CodeCept\Test\Unit;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Block\Product\ProductList\Related;
use Magento\Catalog\Block\Product\ProductList\Upsell;
use Magento\Catalog\Model\Product;
use Magento\Framework\Registry;
use Mockery;
use Tweakwise\Magento2Tweakwise\Block\Catalog\Product\ProductList\Related\Plugin as RelatedPlugin;
use Tweakwise\Magento2Tweakwise\Block\Catalog\Product\ProductList\Upsell\Plugin as UpsellPlugin;
use Tweakwise\Magento2Tweakwise\Model\Client;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\Recommendations\ProductRequest;
use Tweakwise\Magento2Tweakwise\Model\Client\RequestPool;
use Tweakwise\Magento2Tweakwise\Model\Client\Response\RecommendationsResponse;
use Tweakwise\Magento2Tweakwise\Model\Client\Timer;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\ItemType;
use Tweakwise\Magento2Tweakwise\Model\Config\TemplateFinder;
use Tweakwise\Magento2Tweakwise\Model\Visual;
use Tweakwise\Magento2TweakwiseExport\Model\Helper;
use Tweakwise\Test\Support\FunctionalTester;

class RecommendationVisualsTest extends Unit
{
    private const VISUAL_ID = '979a0492-5beb-42be-925a-dca7c24de993';
    private const VISUAL_IMAGE = 'https://example.com/media/wysiwyg/winter.jpg';
    private const VISUAL_URL = 'https://example.com/winter';

    protected FunctionalTester $tester;

    private ProductFixture $currentProduct;

    private ProductFixture $firstProduct;

    private ProductFixture $secondProduct;

    // phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
    /**
     * @return void
     */
    public function _before(): void
    {
        $this->currentProduct = $this->createProduct('tweakwise-visuals-current');
        $this->firstProduct = $this->createProduct('tweakwise-visuals-first');
        $this->secondProduct = $this->createProduct('tweakwise-visuals-second');

        $this->tester->mockConfig('tweakwise/general/enabled', '1');
        $this->tester->mockConfig('tweakwise/general/analytics_enabled', '0');
        $this->tester->mockConfig('tweakwise/recommendations/upsell_enabled', '1');
        $this->tester->mockConfig('tweakwise/recommendations/crosssell_enabled', '1');
        $this->tester->mockConfig('tweakwise/recommendations/batch_requests', '0');
        $this->tester->mockConfig('tweakwise/merchandising_builder/visuals/enabled', '1');

        $templateFinder = Mockery::mock(TemplateFinder::class);
        $templateFinder->shouldReceive('forProduct')->andReturn(10);
        $this->tester->mockService(TemplateFinder::class, $templateFinder);
    }

    /**
     * @return void
     */
    public function testUpsellContainsVisualOnPositionFromTweakwise(): void
    {
        $this->mockRecommendations([
            $this->visualItem(),
            $this->productItem($this->firstProduct),
            $this->productItem($this->secondProduct),
        ]);

        $items = array_values($this->getUpsellItems());

        $this->assertCount(3, $items);
        $this->assertVisual($items[0]);
        $this->assertSame($this->firstProduct->getSku(), $items[1]->getSku());
        $this->assertSame($this->secondProduct->getSku(), $items[2]->getSku());
    }

    /**
     * @return void
     */
    public function testRelatedContainsVisualOnPositionFromTweakwise(): void
    {
        $this->mockRecommendations([
            $this->productItem($this->firstProduct),
            $this->visualItem(),
            $this->productItem($this->secondProduct),
        ]);

        $items = array_values($this->getRelatedItems());

        $this->assertCount(3, $items);
        $this->assertSame($this->firstProduct->getSku(), $items[0]->getSku());
        $this->assertVisual($items[1]);
        $this->assertSame($this->secondProduct->getSku(), $items[2]->getSku());
    }

    /**
     * @return void
     */
    public function testProductsAreKeyedByProductIdSoCartItemsCanBeRemoved(): void
    {
        $this->mockRecommendations([
            $this->visualItem(),
            $this->productItem($this->firstProduct),
            $this->productItem($this->secondProduct),
        ]);

        $items = $this->getUpsellItems();

        $this->assertArrayHasKey((int)$this->firstProduct->getId(), $items);
        $this->assertArrayHasKey((int)$this->secondProduct->getId(), $items);
    }

    /**
     * @return void
     */
    public function testVisualIsLeftOutWhenVisualsAreDisabled(): void
    {
        $this->tester->mockConfig('tweakwise/merchandising_builder/visuals/enabled', '0');
        $this->mockRecommendations([
            $this->visualItem(),
            $this->productItem($this->firstProduct),
            $this->productItem($this->secondProduct),
        ]);

        $items = array_values($this->getUpsellItems());

        $this->assertCount(2, $items);
        $this->assertSame($this->firstProduct->getSku(), $items[0]->getSku());
        $this->assertSame($this->secondProduct->getSku(), $items[1]->getSku());
    }

    /**
     * @return void
     */
    public function testRecommendationsWithoutVisualAreUnchanged(): void
    {
        $this->mockRecommendations([
            $this->productItem($this->firstProduct),
            $this->productItem($this->secondProduct),
        ]);

        $items = array_values($this->getUpsellItems());

        $this->assertCount(2, $items);
        $this->assertNotInstanceOf(Visual::class, $items[0]);
        $this->assertNotInstanceOf(Visual::class, $items[1]);
    }

    /**
     * @return void
     */
    public function testUpsellFromTweakwiseXmlResponseContainsVisualAndProducts(): void
    {
        $this->mockRecommendationsResponse($this->parseXmlFixture('upsell-with-visual.xml'));

        $items = array_values($this->getUpsellItems());

        $this->assertCount(3, $items);
        $this->assertInstanceOf(Visual::class, $items[0]);
        $this->assertSame('https://example.com/media/wysiwyg/winter.jpg', $items[0]->getImageUrl());
        $this->assertSame('https://example.com/winter', $items[0]->getUrl());
        $this->assertSame('979a0492-5beb-42be-925a-dca7c24de993', (string)$items[0]->getId());
        $this->assertSame($this->firstProduct->getSku(), $items[1]->getSku());
        $this->assertSame($this->secondProduct->getSku(), $items[2]->getSku());
    }

    /**
     * @return void
     */
    public function testUpsellFromTweakwiseXmlResponseHasNoVisualWhenVisualsAreDisabled(): void
    {
        $this->tester->mockConfig('tweakwise/merchandising_builder/visuals/enabled', '0');
        $this->mockRecommendationsResponse($this->parseXmlFixture('upsell-with-visual.xml'));

        $items = array_values($this->getUpsellItems());

        $this->assertCount(2, $items);
        $this->assertSame($this->firstProduct->getSku(), $items[0]->getSku());
        $this->assertSame($this->secondProduct->getSku(), $items[1]->getSku());
    }

    /**
     * @param mixed $item
     * @return void
     */
    private function assertVisual(mixed $item): void
    {
        $this->assertInstanceOf(Visual::class, $item);
        $this->assertSame(self::VISUAL_IMAGE, $item->getImageUrl());
        $this->assertSame(self::VISUAL_URL, $item->getUrl());
        $this->assertSame(self::VISUAL_ID, (string)$item->getId());
    }

    /**
     * @return array
     */
    private function getUpsellItems(): array
    {
        $subject = Mockery::mock(Upsell::class);
        $subject->shouldReceive('getProduct')->andReturn($this->registerCurrentProduct());

        /** @var UpsellPlugin $plugin */
        $plugin = $this->tester->getObjectManager()->get(UpsellPlugin::class);

        return $plugin->aroundGetItemCollection($subject, $this->proceedShouldNotBeCalled())->getItems();
    }

    /**
     * @return array
     */
    private function getRelatedItems(): array
    {
        $subject = Mockery::mock(Related::class);
        $subject->shouldReceive('getProduct')->andReturn($this->registerCurrentProduct());

        /** @var RelatedPlugin $plugin */
        $plugin = $this->tester->getObjectManager()->get(RelatedPlugin::class);

        return $plugin->aroundGetItems($subject, $this->proceedShouldNotBeCalled())->getItems();
    }

    /**
     * @return Closure
     */
    private function proceedShouldNotBeCalled(): Closure
    {
        return function () {
            $this->fail('Magento native items were used instead of the Tweakwise recommendations');
        };
    }

    /**
     * @return Product
     */
    private function registerCurrentProduct(): Product
    {
        $objectManager = $this->tester->getObjectManager();

        /** @var Product $product */
        $product = $objectManager->get(ProductRepositoryInterface::class)->get($this->currentProduct->getSku());

        /** @var Registry $registry */
        $registry = $objectManager->get(Registry::class);
        $registry->unregister('product');
        $registry->register('product', $product);

        return $product;
    }

    /**
     * @param ItemType[] $items
     * @return void
     */
    private function mockRecommendations(array $items): void
    {
        /** @var RecommendationsResponse $response */
        $response = $this->tester->getObjectManager()->create(
            RecommendationsResponse::class,
            ['data' => ['items' => $items]]
        );

        $this->mockRecommendationsResponse($response);
    }

    /**
     * @param RecommendationsResponse $response
     * @return void
     */
    private function mockRecommendationsResponse(RecommendationsResponse $response): void
    {
        $requestPool = Mockery::mock(RequestPool::class);
        $requestPool->shouldReceive('resolve')->andReturn($response);
        $requestPool->shouldReceive('add', 'has', 'settle')->byDefault();
        $this->tester->mockService(RequestPool::class, $requestPool);
    }

    /**
     * @param string $fixture
     * @return RecommendationsResponse
     */
    private function parseXmlFixture(string $fixture): RecommendationsResponse
    {
        $objectManager = $this->tester->getObjectManager();

        /** @var Helper $helper */
        $helper = $objectManager->get(Helper::class);
        $xml = str_replace(
            ['{{PRODUCT_1}}', '{{PRODUCT_2}}'],
            [
                $helper->getTweakwiseId(1, (int)$this->firstProduct->getId()),
                $helper->getTweakwiseId(1, (int)$this->secondProduct->getId()),
            ],
            (string)file_get_contents(codecept_data_dir('recommendations/' . $fixture))
        );

        /** @var ProductRequest $request */
        $request = $objectManager->create(ProductRequest::class);

        // Client::handleRequestSuccess() stops the timer, that is normally started when the request is sent.
        $objectManager->get(Timer::class)->startTimer($request->getPath());

        /** @var Client $client */
        $client = $objectManager->get(Client::class);

        $response = $client->handleRequestSuccess(
            new HttpResponse(200, ['Content-Type' => 'text/xml'], $xml),
            new HttpRequest('GET', 'https://gateway.tweakwise.test/recommendations/grouped/key/1/up-sell'),
            $request
        );
        $this->assertInstanceOf(RecommendationsResponse::class, $response);

        return $response;
    }

    /**
     * @return ItemType
     */
    private function visualItem(): ItemType
    {
        return $this->tester->getObjectManager()->create(
            ItemType::class,
            [
                'data' => [
                    ItemType::ID => self::VISUAL_ID,
                    ItemType::TYPE => 'visual',
                    ItemType::TITLE => 'Winter',
                    ItemType::IMAGE => self::VISUAL_IMAGE,
                    ItemType::URL => self::VISUAL_URL,
                    ItemType::VISUAL_PROPERTIES => [ItemType::COLSPAN => 1, ItemType::ROWSPAN => 1],
                ],
            ]
        );
    }

    /**
     * @param ProductFixture $product
     * @return ItemType
     */
    private function productItem(ProductFixture $product): ItemType
    {
        /** @var Helper $helper */
        $helper = $this->tester->getObjectManager()->get(Helper::class);

        return $this->tester->getObjectManager()->create(
            ItemType::class,
            [
                'data' => [
                    ItemType::ID => $helper->getTweakwiseId(1, (int)$product->getId()),
                    ItemType::TYPE => 'product',
                    ItemType::TITLE => $product->getName(),
                ],
            ]
        );
    }

    /**
     * @param string $sku
     * @return ProductFixture
     */
    private function createProduct(string $sku): ProductFixture
    {
        /** @var ProductFixture $product */
        $product = $this->tester->getObjectManager()->create(ProductFixture::class, ['sku' => $sku]);
        $this->tester->createFixture($product);

        return $product;
    }
}
