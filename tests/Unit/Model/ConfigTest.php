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
use Tweakwise\Magento2Tweakwise\Exception\InvalidArgumentException;

class ConfigTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    public function testRequestTimeoutUsesStoreConfiguration(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->with('tweakwise/general/timeout', 'store', null)->willReturn('2');

        $this->assertSame(2, $this->createConfig($scopeConfig)->getTimeout());
    }

    public function testConnectTimeoutUsesStoreConfiguration(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->with('tweakwise/general/connect_timeout', 'store', null)->willReturn('1');

        $this->assertSame(1, $this->createConfig($scopeConfig)->getConnectTimeout());
    }

    /**
     * @dataProvider invalidTimeouts
     */
    public function testInvalidTimeoutCannotDisableTheRequestDeadline(string $value): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturn($value);

        $this->expectException(InvalidArgumentException::class);
        $this->createConfig($scopeConfig)->getTimeout();
    }

    public static function invalidTimeouts(): array
    {
        return [['0'], ['-1'], ['invalid'], ['']];
    }

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
     * @return void
     */
    public function testIsPaginatedCanonicalEnabledReturnsTrueWhenSeoEnabledAndFlagEnabled(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->expects($this->exactly(2))
            ->method('getValue')
            ->willReturnMap([
                ['tweakwise/seo/enabled', 'store', null, '1'],
                ['tweakwise/seo/paginated_canonical_enabled', 'store', null, '1'],
            ]);

        $config = $this->createConfig($scopeConfig);

        $this->assertTrue($config->isPaginatedCanonicalEnabled());
    }

    /**
     * @return void
     */
    public function testIsPaginatedCanonicalEnabledReturnsFalseWhenSeoDisabledAndFlagEnabled(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->expects($this->once())
            ->method('getValue')
            ->with('tweakwise/seo/enabled', 'store', null)
            ->willReturn('0');

        $config = $this->createConfig($scopeConfig);

        $this->assertFalse($config->isPaginatedCanonicalEnabled());
    }

    /**
     * @return void
     */
    public function testIsPaginatedCanonicalEnabledReturnsFalseWhenConfigValueIsZero(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->expects($this->exactly(2))
            ->method('getValue')
            ->willReturnMap([
                ['tweakwise/seo/enabled', 'store', null, '1'],
                ['tweakwise/seo/paginated_canonical_enabled', 'store', null, '0'],
            ]);

        $config = $this->createConfig($scopeConfig);

        $this->assertFalse($config->isPaginatedCanonicalEnabled());
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
