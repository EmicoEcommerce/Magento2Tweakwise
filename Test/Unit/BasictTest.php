<?php

namespace Integration;

use PHPUnit\Framework\TestCase;

class BasictTest extends TestCase
{
    public function testTestEnvironmentIsSetupCorrectly()
    {
        $condition = true;
        $this->assertTrue($condition);
    }
}
