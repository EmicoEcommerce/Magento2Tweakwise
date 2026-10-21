<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Client;

use Emico\CodeCept\Test\Unit;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Variable\Model\ResourceModel\Variable as VariableResource;
use Magento\Variable\Model\Variable;
use Magento\Variable\Model\VariableFactory;
use Mockery;
use Tweakwise\Magento2Tweakwise\Model\Client\EndpointManager;

class EndpointManagerTest extends Unit
{
    public function testConnectFailureAfterDowntimeExpiresSwitchesToFallbackAndRenewsTimer(): void
    {
        $now = 1_800_000_000;
        $variable = Mockery::mock(Variable::class)->makePartial();
        $variable->shouldReceive('getCode')
            ->once()
            ->andReturn(EndpointManager::VARIABLE_NAME);
        $variable->shouldReceive('getValue')
            ->once()
            ->with(Variable::TYPE_TEXT)
            ->andReturn($now - 100);
        $variable->shouldReceive('setData')
            ->once()
            ->with('plain_value', $now + EndpointManager::DOWN_PERIOD)
            ->andReturnSelf();

        $factory = Mockery::mock(VariableFactory::class);
        $factory->shouldReceive('create')->once()->andReturn($variable);

        $resource = Mockery::mock(VariableResource::class);
        $resource->shouldReceive('loadByCode')
            ->once()
            ->with($variable, EndpointManager::VARIABLE_NAME);
        $resource->shouldReceive('save')->once()->with($variable);

        $dateTime = Mockery::mock(DateTime::class);
        $dateTime->shouldReceive('gmtTimestamp')->once()->andReturn($now);

        $manager = new EndpointManager($factory, $resource, $dateTime);
        $manager->handleConnectException();

        $this->assertTrue($manager->isFallback());
    }
}
