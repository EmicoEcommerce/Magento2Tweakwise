<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Setup\Patch\Schema;

use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class CleanupLegacyIndexesTweakwiseAttributeSlugTable implements SchemaPatchInterface
{
    /**
     * @param SchemaSetupInterface $schemaSetup
     */
    public function __construct(private readonly SchemaSetupInterface $schemaSetup)
    {
    }

    /**
     * Drop legacy indexes that were created before declarative schema took over.
     * The declarative schema creates its own indexes with canonical names;
     * the old ones are left behind because they were not registered in db_schema_whitelist.json.
     *
     * @return $this
     */
    public function apply(): self
    {
        $setup = $this->schemaSetup;
        $setup->startSetup();

        $connection = $setup->getConnection();
        $tableName = $setup->getTable('tweakwise_attribute_slug');

        $existingIndexes = array_column(
            $connection->fetchAll('SHOW INDEX FROM ' . $connection->quoteIdentifier($tableName)),
            'Key_name'
        );

        foreach (['ATTRIBUTE', 'STORE_SLUG'] as $indexName) {
            if (in_array($indexName, $existingIndexes, true)) {
                $connection->dropIndex($tableName, $indexName);
            }
        }

        $setup->endSetup();

        return $this;
    }

    /**
     * @return class-string[]
     */
    public static function getDependencies(): array
    {
        return [
            AddAttributeCodeToTweakwiseAttributeSlugTable::class,
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
