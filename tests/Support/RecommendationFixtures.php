<?php

declare(strict_types=1);

namespace Tweakwise\Test\Support;

use Emico\CodeCept\Models\Fixtures\ProductFixture;
use GuzzleHttp\Psr7\Request as HttpRequest;
use GuzzleHttp\Psr7\Response as HttpResponse;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Registry;
use Mockery;
use Tweakwise\Magento2Tweakwise\Model\Client;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\Recommendations\ProductRequest;
use Tweakwise\Magento2Tweakwise\Model\Client\RequestPool;
use Tweakwise\Magento2Tweakwise\Model\Client\Response\RecommendationsResponse;
use Tweakwise\Magento2Tweakwise\Model\Client\Timer;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\ItemType;
use Tweakwise\Magento2Tweakwise\Model\Config\TemplateFinder;
use Tweakwise\Magento2Tweakwise\Model\Visual;
use Tweakwise\Magento2TweakwiseExport\Model\Helper;

/**
 * Builds Tweakwise recommendation responses for the functional tests, so a test can follow the items from
 * the response to what a block hands over to its template.
 *
 * @property FunctionalTester $tester
 */
trait RecommendationFixtures
{
    private const VISUAL_IMAGE = 'https://example.com/media/wysiwyg/winter.jpg';

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

    /**
     * @return void
     */
    private function enableRecommendations(): void
    {
        $this->tester->mockConfig('tweakwise/general/enabled', '1');
        $this->tester->mockConfig('tweakwise/general/analytics_enabled', '0');
        $this->tester->mockConfig('tweakwise/recommendations/upsell_enabled', '1');
        $this->tester->mockConfig('tweakwise/recommendations/crosssell_enabled', '1');
        $this->tester->mockConfig('tweakwise/recommendations/shoppingcart_crosssell_enabled', '1');
        $this->tester->mockConfig('tweakwise/recommendations/featured_enabled', '1');
        $this->tester->mockConfig('tweakwise/recommendations/featured_template', '10');
        $this->tester->mockConfig('tweakwise/recommendations/batch_requests', '0');
        $this->tester->mockConfig('tweakwise/merchandising_builder/visuals/enabled', '1');
        $this->tester->mockConfig('tweakwise/merchandising_builder/personal_merchandising/enabled', '0');

        $templateFinder = Mockery::mock(TemplateFinder::class);
        $templateFinder->shouldReceive('forProduct')->andReturn(10);
        $this->tester->mockService(TemplateFinder::class, $templateFinder);
    }

    /**
     * @param Product $product
     * @return Product
     */
    private function registerCurrentProduct(Product $product): Product
    {
        /** @var Registry $registry */
        $registry = $this->tester->getObjectManager()->get(Registry::class);
        $registry->unregister('product');
        $registry->register('product', $product);

        return $product;
    }

    /**
     * @param ProductFixture $fixture
     * @return Product
     */
    private function loadProduct(ProductFixture $fixture): Product
    {
        return $this->tester->getObjectManager()->get(ProductRepositoryInterface::class)->get($fixture->getSku());
    }

    /**
     * Tweakwise answers with these items, whatever the request is.
     *
     * @param ItemType[] $items
     * @return RecommendationsResponse
     */
    private function mockRecommendations(array $items): RecommendationsResponse
    {
        /** @var RecommendationsResponse $response */
        $response = $this->tester->getObjectManager()->create(
            RecommendationsResponse::class,
            ['data' => ['items' => $items]]
        );

        $this->mockRecommendationsResponse($response);

        return $response;
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
     * Reads a Tweakwise XML response through the client, the same way a response of the gateway is read.
     *
     * @param string $fixture
     * @param ProductFixture[] $products replaces {{PRODUCT_1}}, {{PRODUCT_2}} and so on
     * @return RecommendationsResponse
     */
    private function parseXmlFixture(string $fixture, array $products): RecommendationsResponse
    {
        $objectManager = $this->tester->getObjectManager();

        /** @var Helper $helper */
        $helper = $objectManager->get(Helper::class);
        $search = [];
        $replace = [];
        foreach (array_values($products) as $index => $product) {
            $search[] = '{{PRODUCT_' . ($index + 1) . '}}';
            $replace[] = $helper->getTweakwiseId(1, (int)$product->getId());
        }

        $xml = str_replace($search, $replace, (string)file_get_contents(codecept_data_dir('recommendations/' . $fixture)));

        /** @var ProductRequest $request */
        $request = $objectManager->create(ProductRequest::class);

        // Client::handleRequestSuccess() stops the timer, that is normally started when the request is sent.
        $objectManager->get(Timer::class)->startTimer($request->getPath());

        $response = $objectManager->get(Client::class)->handleRequestSuccess(
            new HttpResponse(200, ['Content-Type' => 'text/xml'], $xml),
            new HttpRequest('GET', 'https://gateway.tweakwise.test/recommendations/grouped/key/1/up-sell'),
            $request
        );
        $this->assertInstanceOf(RecommendationsResponse::class, $response);

        return $response;
    }

    /**
     * @param string $id
     * @return ItemType
     */
    private function visualItem(string $id): ItemType
    {
        return $this->tester->getObjectManager()->create(
            ItemType::class,
            [
                'data' => [
                    ItemType::ID => $id,
                    ItemType::TYPE => 'visual',
                    ItemType::TITLE => 'Visual ' . $id,
                    ItemType::IMAGE => self::VISUAL_IMAGE,
                    ItemType::URL => 'https://example.com/' . $id,
                    ItemType::ATTRIBUTES => [],
                    ItemType::VISUAL_PROPERTIES => [ItemType::COLSPAN => 1, ItemType::ROWSPAN => 1],
                ],
            ]
        );
    }

    /**
     * With grouped products Tweakwise adds the group code of the product to its attributes.
     *
     * @param ProductFixture $product
     * @param bool $withGroupCode
     * @return ItemType
     */
    private function productItem(ProductFixture $product, bool $withGroupCode = false): ItemType
    {
        /** @var Helper $helper */
        $helper = $this->tester->getObjectManager()->get(Helper::class);
        $tweakwiseId = $helper->getTweakwiseId(1, (int)$product->getId());

        $data = [
            ItemType::ID => $tweakwiseId,
            ItemType::TYPE => 'product',
            ItemType::TITLE => $product->getName(),
            ItemType::ATTRIBUTES => [],
        ];

        if ($withGroupCode) {
            $data[ItemType::ATTRIBUTES] = [
                'attribute' => [['name' => 'groupcode', 'values' => ['value' => $tweakwiseId]]],
            ];
        }

        return $this->tester->getObjectManager()->create(ItemType::class, ['data' => $data]);
    }

    /**
     * Describes the items as "V" (visual) or the sku of the product, to compare the order in one assertion.
     *
     * @param iterable $items
     * @return string[]
     */
    private function describe(iterable $items): array
    {
        $described = [];
        foreach ($items as $item) {
            $described[] = $item instanceof Visual ? 'V' : $item->getSku();
        }

        return $described;
    }
}
