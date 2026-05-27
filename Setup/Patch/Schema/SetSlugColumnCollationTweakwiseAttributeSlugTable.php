<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Setup\Patch\Schema;

use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class SetSlugColumnCollationTweakwiseAttributeSlugTable implements SchemaPatchInterface
{
    /**
     * @param SchemaSetupInterface $schemaSetup
     */
    public function __construct(private readonly SchemaSetupInterface $schemaSetup)
    {
    }

    /**
     * Change the slug column to utf8mb4_bin collation so that accent-distinct slugs
     * (e.g. "e" vs "é" after transliteration) are treated as unique by the DB index.
     * utf8mb4_bin is chosen over utf8mb4_0900_as_cs for compatibility with MariaDB.
     * Slugs are always lowercased ASCII after transliteration, so binary comparison
     * is safe and equivalent to accent+case-sensitive for this data.
     *
     * @return $this
     */
    public function apply(): self
    {
        $setup = $this->schemaSetup;
        $setup->startSetup();

        $connection = $setup->getConnection();
        $tableName = $connection->quoteIdentifier($setup->getTable('tweakwise_attribute_slug'));

        $connection->query(
            'ALTER TABLE ' . $tableName
            . ' MODIFY COLUMN `slug` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL'
            . " COMMENT 'URL Slug'"
        );

        $setup->endSetup();

        return $this;
    }

    /**
     * @return class-string[]
     */
    public static function getDependencies(): array
    {
        return [
            CleanupLegacyIndexesTweakwiseAttributeSlugTable::class,
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
