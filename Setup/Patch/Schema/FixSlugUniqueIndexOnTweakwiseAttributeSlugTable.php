<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Setup\Patch\Schema;

use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class FixSlugUniqueIndexOnTweakwiseAttributeSlugTable implements SchemaPatchInterface
{
    /**
     * @param SchemaSetupInterface $schemaSetup
     */
    public function __construct(private readonly SchemaSetupInterface $schemaSetup)
    {
    }

    /**
     * @return $this
     */
    public function apply()
    {
        $setup = $this->schemaSetup;
        $setup->startSetup();
        $connection = $setup->getConnection();
        $tableName = $setup->getTable('tweakwise_attribute_slug');

        if (!$connection->isTableExists($tableName)) {
            $setup->endSetup();
            return $this;
        }

        $indexes = $connection->getIndexList($tableName);

        // Drop the old global unique index on slug (added by InstallSchema).
        // The index name Magento generates is based on the table + column names.
        foreach ($indexes as $indexName => $indexData) {
            $columns = array_map('strtolower', $indexData['COLUMNS_LIST'] ?? []);
            if ($columns === ['slug'] && strtoupper($indexData['INDEX_TYPE'] ?? '') === 'UNIQUE') {
                $connection->dropIndex($tableName, $indexName);
                break;
            }
        }

        // Add composite unique index on (slug, store_id) if it doesn't exist yet.
        $compositeExists = false;
        foreach ($connection->getIndexList($tableName) as $indexData) {
            $columns = array_map('strtolower', $indexData['COLUMNS_LIST'] ?? []);
            sort($columns);
            $expected = ['slug', 'store_id'];
            sort($expected);
            if ($columns === $expected && strtoupper($indexData['INDEX_TYPE'] ?? '') === 'UNIQUE') {
                $compositeExists = true;
                break;
            }
        }

        if (!$compositeExists) {
            $connection->addIndex(
                $tableName,
                $setup->getIdxName($tableName, ['slug', 'store_id'], 'unique'),
                ['slug', 'store_id'],
                'unique'
            );
        }

        $setup->endSetup();

        return $this;
    }

    /**
     * @return class-string[]
     */
    public static function getDependencies()
    {
        return [
            ChangePrimaryKeyTweakwiseAttributeSlugTable::class,
        ];
    }

    /**
     * @return string[]
     */
    public function getAliases()
    {
        return [];
    }
}
