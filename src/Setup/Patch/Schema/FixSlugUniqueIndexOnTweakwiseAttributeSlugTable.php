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

        if ($connection->isTableExists($tableName)) {
            $this->dropGlobalSlugIndex($tableName);
            $this->addCompositeSlugStoreIndex($tableName);
        }

        $setup->endSetup();

        return $this;
    }

    /**
     * Drops the global unique index on `slug` that was created by InstallSchema.
     *
     * @param string $tableName
     * @return void
     */
    private function dropGlobalSlugIndex(string $tableName): void
    {
        $connection = $this->schemaSetup->getConnection();

        foreach ($connection->getIndexList($tableName) as $indexName => $indexData) {
            $columns = array_map('strtolower', $indexData['COLUMNS_LIST'] ?? []);
            if ($columns === ['slug'] && strtoupper($indexData['INDEX_TYPE'] ?? '') === 'UNIQUE') {
                $connection->dropIndex($tableName, $indexName);
                break;
            }
        }
    }

    /**
     * Adds a composite unique index on `(slug, store_id)` if it does not exist yet.
     *
     * @param string $tableName
     * @return void
     */
    private function addCompositeSlugStoreIndex(string $tableName): void
    {
        $setup = $this->schemaSetup;
        $connection = $setup->getConnection();

        foreach ($connection->getIndexList($tableName) as $indexData) {
            $columns = array_map('strtolower', $indexData['COLUMNS_LIST'] ?? []);
            sort($columns);
            if ($columns === ['slug', 'store_id'] && strtoupper($indexData['INDEX_TYPE'] ?? '') === 'UNIQUE') {
                return;
            }
        }

        $connection->addIndex(
            $tableName,
            $setup->getIdxName($tableName, ['slug', 'store_id'], 'unique'),
            ['slug', 'store_id'],
            'unique'
        );
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
