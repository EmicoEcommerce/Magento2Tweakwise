<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Autocomplete\DataProvider;

use Emico\CodeCept\Test\Unit;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\RejectedPromise;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Mockery;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionProperty;
use stdClass;
use Tweakwise\Magento2Tweakwise\Exception\ApiException;
use Tweakwise\Magento2Tweakwise\Model\Autocomplete\DataProvider\SuggestionDataProvider;
use Tweakwise\Magento2Tweakwise\Model\Autocomplete\DataProviderHelper;
use Tweakwise\Magento2Tweakwise\Model\Client;
use Tweakwise\Magento2Tweakwise\Model\Client\Request;
use Tweakwise\Magento2Tweakwise\Model\Config;

class SuggestionDataProviderTest extends Unit
{
    public function testRejectedSuggestionRequestsReturnNoItemsAndDisableFurtherRequests(): void
    {
        $helper = Mockery::mock(DataProviderHelper::class);
        $helper->shouldReceive('getQuery')->once()->andReturn('query');
        $helper->shouldReceive('getCategory')->once()->andReturn(0);
        $helper->shouldReceive('addVisibilityFilter')->twice();

        $config = Mockery::mock(Config::class);
        $config->shouldReceive('getTweakwiseExceptionTrown')->andReturn(false);
        $config->shouldReceive('getPersonalMerchandisingCookieName')->once()->andReturn('cookie');
        $config->shouldReceive('setTweakwiseExceptionThrown')->once();

        $cookieManager = Mockery::mock(CookieManagerInterface::class);
        $cookieManager->shouldReceive('getCookie')->once()->with('cookie', null)->andReturn(null);

        $suggestionRequest = $this->createRequest();
        $productRequest = $this->createRequest();
        $suggestionFactory = new class ($suggestionRequest) {
            public function __construct(private Request $request)
            {
            }

            public function create(): Request
            {
                return $this->request;
            }
        };
        $productFactory = new class ($productRequest) {
            public function __construct(private Request $request)
            {
            }

            public function create(): Request
            {
                return $this->request;
            }
        };

        $client = Mockery::mock(Client::class);
        $client->shouldReceive('request')->twice()->andReturn(
            new RejectedPromise(new ApiException('Tweakwise API is unavailable')),
            new FulfilledPromise(new stdClass())
        );

        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('critical')->once()->with('Tweakwise API is unavailable');

        $provider = (new ReflectionClass(SuggestionDataProvider::class))->newInstanceWithoutConstructor();
        $this->setProperty($provider, 'config', $config);
        $this->setProperty($provider, 'cookieManager', $cookieManager);
        $this->setProperty($provider, 'dataProviderHelper', $helper);
        $this->setProperty($provider, 'suggestionRequestFactory', $suggestionFactory);
        $this->setProperty($provider, 'productSuggestionRequestFactory', $productFactory);
        $this->setProperty($provider, 'client', $client);
        $this->setProperty($provider, 'logger', $logger);

        $this->assertSame([], $provider->getItems());
    }

    private function createRequest(): Request
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('setSearch')->once()->with('query')->andReturnSelf();
        $request->shouldReceive('addCategoryFilter')->once()->with(0)->andReturnSelf();

        return $request;
    }

    private function setProperty(SuggestionDataProvider $provider, string $property, object $value): void
    {
        $reflectionProperty = new ReflectionProperty($provider, $property);
        $reflectionProperty->setValue($provider, $value);
    }
}
