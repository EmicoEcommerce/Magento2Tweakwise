<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Setup\Patch\Schema;

use Emico\CodeCept\Test\Unit;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Tweakwise\Magento2Tweakwise\Setup\Patch\Schema\AddAttributeCodeToTweakwiseAttributeSlugTable;
use Tweakwise\Magento2Tweakwise\Setup\Patch\Schema\CleanupLegacyIndexesTweakwiseAttributeSlugTable;
use Tweakwise\Magento2Tweakwise\Setup\Patch\Schema\SetAttributeColumnCollationTweakwiseAttributeSlugTable;
use Tweakwise\Magento2Tweakwise\Setup\Patch\Schema\SetSlugColumnCollationTweakwiseAttributeSlugTable;

class SchemaPatchesTest extends Unit
{
    use MockeryPHPUnitIntegration;

    private SchemaSetupInterface&MockInterface $schemaSetup;
    private AdapterInterface&MockInterface $connection;

    protected function _before(): void
    {
        $this->schemaSetup = Mockery::mock(SchemaSetupInterface::class);
        $this->connection = Mockery::mock(AdapterInterface::class);
    }

    public function testCleanupLegacyIndexesDropsOnlyExistingStoreSlugLegacyIndex(): void
    {
        $tableName = 'prefix_tweakwise_attribute_slug';

        $this->schemaSetup->shouldReceive('startSetup')->once();
        $this->schemaSetup->shouldReceive('getConnection')->once()->andReturn($this->connection);
        $this->schemaSetup->shouldReceive('getTable')->once()->with('tweakwise_attribute_slug')->andReturn($tableName);
        $this->schemaSetup->shouldReceive('endSetup')->once();

        $this->connection->shouldReceive('quoteIdentifier')->once()->with($tableName)->andReturn('`' . $tableName . '`');
        $this->connection->shouldReceive('fetchAll')
            ->once()
            ->with('SHOW INDEX FROM `' . $tableName . '`')
            ->andReturn([
                ['Key_name' => 'PRIMARY'],
                ['Key_name' => 'ATTRIBUTE'],
                ['Key_name' => 'STORE_SLUG'],
            ]);
        $this->connection->shouldReceive('dropIndex')->once()->with($tableName, 'STORE_SLUG');

        $patch = new CleanupLegacyIndexesTweakwiseAttributeSlugTable($this->schemaSetup);

        $this->assertSame($patch, $patch->apply());
    }

    public function testCleanupLegacyIndexesSkipsDropWhenLegacyIndexesAreMissing(): void
    {
        $tableName = 'prefix_tweakwise_attribute_slug';

        $this->schemaSetup->shouldReceive('startSetup')->once();
        $this->schemaSetup->shouldReceive('getConnection')->once()->andReturn($this->connection);
        $this->schemaSetup->shouldReceive('getTable')->once()->with('tweakwise_attribute_slug')->andReturn($tableName);
        $this->schemaSetup->shouldReceive('endSetup')->once();

        $this->connection->shouldReceive('quoteIdentifier')->once()->with($tableName)->andReturn('`' . $tableName . '`');
        $this->connection->shouldReceive('fetchAll')
            ->once()
            ->with('SHOW INDEX FROM `' . $tableName . '`')
            ->andReturn([
                ['Key_name' => 'PRIMARY'],
            ]);
        $this->connection->shouldNotReceive('dropIndex');

        $patch = new CleanupLegacyIndexesTweakwiseAttributeSlugTable($this->schemaSetup);

        $patch->apply();
    }

    public function testSetSlugColumnCollationRunsExpectedAlterTableQuery(): void
    {
        $tableName = 'prefix_tweakwise_attribute_slug';

        $this->schemaSetup->shouldReceive('startSetup')->once();
        $this->schemaSetup->shouldReceive('getConnection')->once()->andReturn($this->connection);
        $this->schemaSetup->shouldReceive('getTable')->once()->with('tweakwise_attribute_slug')->andReturn($tableName);
        $this->schemaSetup->shouldReceive('endSetup')->once();

        $this->connection->shouldReceive('quoteIdentifier')->once()->with($tableName)->andReturn('`' . $tableName . '`');
        $this->connection->shouldReceive('query')
            ->once()
            ->with(
                'ALTER TABLE `' . $tableName . '`'
                . ' MODIFY COLUMN `slug` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL'
                . " COMMENT 'URL Slug'"
            );

        $patch = new SetSlugColumnCollationTweakwiseAttributeSlugTable($this->schemaSetup);

        $this->assertSame($patch, $patch->apply());
    }

    public function testSetAttributeColumnCollationRunsExpectedAlterTableQuery(): void
    {
        $tableName = 'prefix_tweakwise_attribute_slug';

        $this->schemaSetup->shouldReceive('startSetup')->once();
        $this->schemaSetup->shouldReceive('getConnection')->once()->andReturn($this->connection);
        $this->schemaSetup->shouldReceive('getTable')->once()->with('tweakwise_attribute_slug')->andReturn($tableName);
        $this->schemaSetup->shouldReceive('endSetup')->once();

        $this->connection->shouldReceive('quoteIdentifier')->once()->with($tableName)->andReturn('`' . $tableName . '`');
        $this->connection->shouldReceive('query')
            ->once()
            ->with(
                'ALTER TABLE `' . $tableName . '`'
                . ' MODIFY COLUMN `attribute` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL'
                . " COMMENT 'Attribute code'"
            );

        $patch = new SetAttributeColumnCollationTweakwiseAttributeSlugTable($this->schemaSetup);

        $this->assertSame($patch, $patch->apply());
    }

    public function testPatchDependenciesAndAliases(): void
    {
        $this->assertSame(
            [AddAttributeCodeToTweakwiseAttributeSlugTable::class],
            CleanupLegacyIndexesTweakwiseAttributeSlugTable::getDependencies(),
        );
        $this->assertSame(
            [CleanupLegacyIndexesTweakwiseAttributeSlugTable::class],
            SetSlugColumnCollationTweakwiseAttributeSlugTable::getDependencies(),
        );
        $this->assertSame(
            [SetSlugColumnCollationTweakwiseAttributeSlugTable::class],
            SetAttributeColumnCollationTweakwiseAttributeSlugTable::getDependencies(),
        );
        $this->assertSame([], (new CleanupLegacyIndexesTweakwiseAttributeSlugTable($this->schemaSetup))->getAliases());
        $this->assertSame([], (new SetSlugColumnCollationTweakwiseAttributeSlugTable($this->schemaSetup))->getAliases());
        $this->assertSame([], (new SetAttributeColumnCollationTweakwiseAttributeSlugTable($this->schemaSetup))->getAliases());
    }
}
