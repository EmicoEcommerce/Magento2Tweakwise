<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Setup\Patch\Schema;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class AddAttributeCodeToTweakwiseAttributeSlugTable implements SchemaPatchInterface
{
    /**
     * Constructor.
     *
     * @param SchemaSetupInterface $schemaSetup
     */
    public function __construct(private readonly SchemaSetupInterface $schemaSetup)
    {
    }

    /**
     * @return $this|AddAttributeCodeToTweakwiseAttributeSlugTable
     */
    public function apply()
    {
        $setup = $this->schemaSetup;
        $setup->startSetup();
        $connection = $setup->getConnection();
        $tableName = $setup->getTable('tweakwise_attribute_slug');

        if (
            $connection->isTableExists($tableName)
            && !$connection->tableColumnExists($tableName, 'attribute_code')
        ) {
            $connection->addColumn($tableName, 'attribute_code', [
                'type' => Table::TYPE_TEXT,
                'length' => 255,
                'nullable' => true,
                'default' => null,
                'comment' => 'Attribute code',
            ]);
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
     * @return array|string[]
     */
    public function getAliases()
    {
        return [];
    }
}
