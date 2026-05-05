<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Setup\Patch\Schema;

use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class FixSlugUniqueIndexTweakwiseAttributeSlugTable implements SchemaPatchInterface
{
    /**
     * @param SchemaSetupInterface $schemaSetup
     */
    public function __construct(private readonly SchemaSetupInterface $schemaSetup)
    {
    }

    /**
     * Drops the global unique index on `slug` and replaces it with a composite
     * unique index on `(store_id, slug)` so that the same slug may exist across
     * different stores while still being unique within a single store.
     *
     * @return $this
     */
    public function apply()
    {
        $setup = $this->schemaSetup;
        $setup->startSetup();
        $connection = $setup->getConnection();
        $tableName = $setup->getTable('tweakwise_attribute_slug');

        $oldIndexName = $setup->getIdxName($tableName, ['slug']);
        $existingIndexes = $connection->getIndexList($tableName);

        if (isset($existingIndexes[strtoupper($oldIndexName)])) {
            $connection->dropIndex($tableName, $oldIndexName);
        }

        $connection->addIndex($tableName, 'STORE_SLUG', ['store_id', 'slug'], 'unique');

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
