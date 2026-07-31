<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Setup\Patch\Schema;

use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class SetAttributeColumnCollationTweakwiseAttributeSlugTable implements SchemaPatchInterface
{
    /**
     * @param SchemaSetupInterface $schemaSetup
     */
    public function __construct(private readonly SchemaSetupInterface $schemaSetup)
    {
    }

    /**
     * Change the attribute column to utf8mb4_bin collation so accent-distinct
     * attribute labels are treated as different values by lookups and indexes.
     *
     * @return $this
     */
    public function apply(): self
    {
        $setup = $this->schemaSetup;
        $setup->startSetup();

        $connection = $setup->getConnection();
        $tableName = $connection->quoteIdentifier($setup->getTable('tweakwise_attribute_slug'));

        // phpcs:disable Magento2.SQL.RawQuery.FoundRawSql -- modifyColumn() has no per-column collation support; ALTER TABLE is the only option
        $connection->query(
            'ALTER TABLE ' . $tableName
            . ' MODIFY COLUMN `attribute` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL'
            . " COMMENT 'Attribute code'"
        );
        // phpcs:enable Magento2.SQL.RawQuery.FoundRawSql

        $setup->endSetup();

        return $this;
    }

    /**
     * @return class-string[]
     */
    public static function getDependencies(): array
    {
        return [
            SetSlugColumnCollationTweakwiseAttributeSlugTable::class,
        ];
    }

    /**
     * @return string[]
     */
    public function getAliases(): array
    {
        return [];
    }
}
