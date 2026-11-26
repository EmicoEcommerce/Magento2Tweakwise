<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Client\Response;

use Emico\CodeCept\Test\Unit;
use Mockery;
use ReflectionMethod;
use SimpleXMLElement;
use Tweakwise\Magento2Tweakwise\Model\Client;
use Tweakwise\Magento2Tweakwise\Model\Client\Request;
use Tweakwise\Magento2Tweakwise\Model\Client\Response\ProductNavigationResponse;
use Tweakwise\Magento2TweakwiseExport\Model\Helper;

class ProductNavigationResponseAssistantTest extends Unit
{
    /**
     * @dataProvider assistantNodes
     */
    public function testAssistantFlagIsParsed(array $data, bool $enabled, ?string $reason): void
    {
        $response = new ProductNavigationResponse(Mockery::mock(Helper::class), Mockery::mock(Request::class), $data);

        $this->assertSame($enabled, $response->isAssistantEnabled());
        $this->assertSame($reason, $response->getAssistantReason());
    }

    /**
     * @dataProvider xmlNodes
     */
    public function testAssistantFlagIsParsedFromXml(string $xml, bool $enabled, ?string $reason): void
    {
        $client = (new \ReflectionClass(Client::class))->newInstanceWithoutConstructor();
        $data = (new ReflectionMethod($client, 'xmlToArray'))->invoke($client, new SimpleXMLElement($xml));
        $response = new ProductNavigationResponse(Mockery::mock(Helper::class), Mockery::mock(Request::class), $data);

        $this->assertSame($enabled, $response->isAssistantEnabled());
        $this->assertSame($reason, $response->getAssistantReason());
    }

    public static function xmlNodes(): array
    {
        return [
            'enabled' => ['<navigation><assistant><enabled>true</enabled><reason/></assistant></navigation>', true, null],
            'quota reached' => [
                '<navigation><assistant><enabled>false</enabled><reason>quota-reached</reason></assistant></navigation>',
                false,
                'quota-reached',
            ],
            'missing node' => ['<navigation><properties/></navigation>', false, null],
        ];
    }

    public static function assistantNodes(): array
    {
        return [
            // XML responses contain strings, an empty <reason/> node is parsed as an empty array
            'enabled (xml)' => [['assistant' => ['enabled' => 'true', 'reason' => []]], true, null],
            'enabled (json)' => [['assistant' => ['enabled' => true, 'reason' => '']], true, null],
            'disabled by template' => [
                ['assistant' => ['enabled' => 'false', 'reason' => 'disabled-by-template']],
                false,
                'disabled-by-template',
            ],
            'quota reached' => [['assistant' => ['enabled' => 'false', 'reason' => 'quota-reached']], false, 'quota-reached'],
            'quota unavailable' => [
                ['assistant' => ['enabled' => 'false', 'reason' => 'quota-unavailable']],
                false,
                'quota-unavailable',
            ],
            'missing node' => [[], false, null],
            'empty node' => [['assistant' => []], false, null],
        ];
    }
}
