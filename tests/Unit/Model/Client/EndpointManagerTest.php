<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Client;

use Emico\CodeCept\Test\Unit;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Variable\Model\ResourceModel\Variable as VariableResource;
use Magento\Variable\Model\Variable;
use Magento\Variable\Model\VariableFactory;
use Mockery;
use stdClass;
use Tweakwise\Magento2Tweakwise\Exception\ApiException;
use Tweakwise\Magento2Tweakwise\Model\Client\EndpointManager;
use Tweakwise\Test\Support\UnitTester;

class EndpointManagerTest extends Unit
{
    protected UnitTester $tester;

    public function testBothGatewayFailuresAreRememberedAcrossPageRequests(): void
    {
        $now = 1_800_000_000;
        $stored = new stdClass();
        $stored->variables = [];
        $manager = $this->createManager($stored, $now);
        $this->assertSame(EndpointManager::SERVER_URL, $manager->getServerUrl());
        $manager->handleConnectException();
        $this->assertSame(EndpointManager::FALLBACK_SERVER_URL, $manager->getServerUrl());
        $manager->handleConnectException(true);

        $nextRequest = $this->createManager($stored, $now);
        $this->expectException(ApiException::class);
        $nextRequest->getServerUrl();
    }

    public function testFallbackIsProbedAgainAfterItsCooldown(): void
    {
        $now = 1_800_000_000;
        $stored = new stdClass();
        $stored->variables = [];
        $manager = $this->createManager($stored, $now);
        $manager->handleConnectException();
        $manager->handleConnectException(true);

        $now += 30;
        $nextRequest = $this->createManager($stored, $now);
        $this->assertSame(EndpointManager::FALLBACK_SERVER_URL, $nextRequest->getServerUrl());

        $now += EndpointManager::DOWN_PERIOD;
        $recovered = $this->createManager($stored, $now);
        $this->assertSame(EndpointManager::SERVER_URL, $recovered->getServerUrl());
    }

    /**
     * @dataProvider primaryTimerOffsets
     */
    public function testConnectFailureAfterDowntimeExpiresSwitchesToFallbackAndRenewsTimer(
        int $offset,
        bool $renew
    ): void {
        $now = 1_800_000_000;
        $variable = Mockery::mock(Variable::class)->makePartial();
        $variable->shouldReceive('getCode')
            ->once()
            ->andReturn(EndpointManager::VARIABLE_NAME);
        $variable->shouldReceive('getValue')
            ->once()
            ->with(Variable::TYPE_TEXT)
            ->andReturn($now + $offset);
        $variable->shouldReceive('setData')
            ->times($renew ? 1 : 0)
            ->with('plain_value', $now + EndpointManager::DOWN_PERIOD)
            ->andReturnSelf();

        $factory = Mockery::mock(VariableFactory::class);
        $factory->shouldReceive('create')->once()->andReturn($variable);

        $resource = Mockery::mock(VariableResource::class);
        $resource->shouldReceive('loadByCode')
            ->once()
            ->with($variable, EndpointManager::VARIABLE_NAME);
        $resource->shouldReceive('save')->times($renew ? 1 : 0)->with($variable);

        $dateTime = Mockery::mock(DateTime::class);
        $dateTime->shouldReceive('gmtTimestamp')->once()->andReturn($now);

        $manager = new EndpointManager($factory, $resource, $dateTime);
        $manager->handleConnectException();

        $this->assertTrue($manager->isFallback());
    }

    public static function primaryTimerOffsets(): array
    {
        return [
            'expired 300 seconds ago' => [-300, true],
            'expired 299 seconds ago' => [-299, true],
            'expired 100 seconds ago' => [-100, true],
            'expired one second ago' => [-1, true],
            'expires now' => [0, true],
            'expires in one second' => [1, false],
            'expires in 100 seconds' => [100, false],
            'expires in 300 seconds' => [300, false],
        ];
    }

    private function createManager(stdClass $stored, int $now): EndpointManager
    {
        $factory = Mockery::mock(VariableFactory::class);
        $factory->shouldReceive('create')->andReturnUsing(
            fn (): Variable => $this->tester->getObjectManager()->create(Variable::class)
        );
        $resource = Mockery::mock(VariableResource::class);
        $resource->shouldReceive('loadByCode')->andReturnUsing(
            static function (Variable $variable, string $code) use ($stored): void {
                if (!isset($stored->variables[$code])) {
                    return;
                }

                $variable->setData($stored->variables[$code]);
            }
        );
        $resource->shouldReceive('save')->andReturnUsing(
            static function (Variable $variable) use ($stored): void {
                $stored->variables[$variable->getCode()] = $variable->getData();
            }
        );
        $dateTime = Mockery::mock(DateTime::class);
        $dateTime->shouldReceive('gmtTimestamp')->andReturnUsing(static fn (): int => $now);

        return new EndpointManager($factory, $resource, $dateTime);
    }
}
