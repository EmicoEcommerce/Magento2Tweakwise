<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Autocomplete\DataProvider;

use Emico\CodeCept\Test\Unit;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\RejectedPromise;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Mockery;
use Psr\Log\LoggerInterface;
use stdClass;
use Tweakwise\Magento2Tweakwise\Exception\ApiException;
use Tweakwise\Magento2Tweakwise\Model\Autocomplete\DataProvider\SuggestionDataProvider;
use Tweakwise\Magento2Tweakwise\Model\Autocomplete\DataProvider\SuggestionBlockItemFactory;
use Tweakwise\Magento2Tweakwise\Model\Autocomplete\DataProvider\SuggestionGroupItemFactory;
use Tweakwise\Magento2Tweakwise\Model\Autocomplete\DataProviderHelper;
use Tweakwise\Magento2Tweakwise\Model\Client;
use Tweakwise\Magento2Tweakwise\Model\Client\Request;
use Tweakwise\Magento2Tweakwise\Model\Client\RequestFactory;
use Tweakwise\Magento2Tweakwise\Model\Config;

class SuggestionDataProviderTest extends Unit
{
    /**
     * @dataProvider failureCodes
     */
    public function testRejectedSuggestionRequestsReturnNoItemsAndDisableFurtherRequests(int $code): void
    {
        $helper = Mockery::mock(DataProviderHelper::class);
        $helper->shouldReceive('getQuery')->once()->andReturn('query');
        $helper->shouldReceive('getCategory')->once()->andReturn(0);
        $helper->shouldReceive('addVisibilityFilter')->twice();

        $config = Mockery::mock(Config::class);
        $config->shouldReceive('getTweakwiseExceptionThrown')->andReturn(false);
        $config->shouldReceive('getPersonalMerchandisingCookieName')->once()->andReturn('cookie');
        if ($code === 404) {
            $config->shouldNotReceive('setTweakwiseExceptionThrown');
        } else {
            $config->shouldReceive('setTweakwiseExceptionThrown')->once();
        }

        $cookieManager = Mockery::mock(CookieManagerInterface::class);
        $cookieManager->shouldReceive('getCookie')->once()->with('cookie', null)->andReturn(null);

        $suggestionRequest = $this->createRequest();
        $productRequest = $this->createRequest();
        $suggestionFactory = Mockery::mock(RequestFactory::class);
        $suggestionFactory->shouldReceive('create')->once()->andReturn($suggestionRequest);
        $productFactory = Mockery::mock(RequestFactory::class);
        $productFactory->shouldReceive('create')->once()->andReturn($productRequest);

        $client = Mockery::mock(Client::class);
        $client->shouldReceive('request')->twice()->andReturn(
            new RejectedPromise(new ApiException('Tweakwise API is unavailable', $code)),
            new FulfilledPromise(new stdClass())
        );

        $logger = Mockery::mock(LoggerInterface::class);
        if ($code === 404) {
            $logger->shouldNotReceive('critical');
        } else {
            $logger->shouldReceive('critical')->once()->with('Tweakwise API is unavailable');
        }

        $provider = new SuggestionDataProvider(
            $config,
            $cookieManager,
            $helper,
            Mockery::mock(SuggestionGroupItemFactory::class),
            $productFactory,
            $suggestionFactory,
            $client,
            Mockery::mock(Request::class),
            Mockery::mock(SuggestionBlockItemFactory::class),
            $logger
        );

        $this->assertSame([], $provider->getItems());
    }

    public static function failureCodes(): array
    {
        return [[0], [404]];
    }

    private function createRequest(): Request
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('setSearch')->once()->with('query')->andReturnSelf();
        $request->shouldReceive('addCategoryFilter')->once()->with(0)->andReturnSelf();

        return $request;
    }
}
