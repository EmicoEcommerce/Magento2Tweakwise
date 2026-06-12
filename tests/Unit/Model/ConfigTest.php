<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model;

use Emico\CodeCept\Test\Unit;
use Magento\Framework\App\Area;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\State;
use Magento\Framework\Serialize\Serializer\Json;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Test\Support\UnitTester;

class ConfigTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    /**
     * @return void
     */
    public function testGetInternalIpAddressesReturnsEmptyArrayWhenConfigIsEmpty(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->expects($this->once())
            ->method('getValue')
            ->with('tweakwise/general/internal_ip_addresses', 'store', null)
            ->willReturn('');

        $config = $this->createConfig($scopeConfig);

        $this->assertSame([], $config->getInternalIpAddresses());
    }

    /**
     * @return void
     */
    public function testGetInternalIpAddressesParsesCommaSeparatedValuesWithWhitespace(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->expects($this->once())
            ->method('getValue')
            ->with('tweakwise/general/internal_ip_addresses', 'store', null)
            ->willReturn('127.0.0.1 , 10.0.0.1 , 192.168.1.1');

        $config = $this->createConfig($scopeConfig);

        $this->assertSame(['127.0.0.1', '10.0.0.1', '192.168.1.1'], $config->getInternalIpAddresses());
    }

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @return Config
     */
    private function createConfig(ScopeConfigInterface $scopeConfig): Config
    {
        $state = $this->createMock(State::class);
        $state->method('getAreaCode')->willReturn(Area::AREA_FRONTEND);

        return new Config(
            $scopeConfig,
            $this->createMock(Json::class),
            $this->createMock(RequestInterface::class),
            $state,
            $this->createMock(WriterInterface::class),
            $this->createMock(TypeListInterface::class)
        );
    }
}
