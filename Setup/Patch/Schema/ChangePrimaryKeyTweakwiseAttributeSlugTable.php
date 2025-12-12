<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Setup\Patch\Schema;

use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class ChangePrimaryKeyTweakwiseAttributeSlugTable implements SchemaPatchInterface
{
    /**
     * @var SchemaSetupInterface
     */
    public function __construct(private readonly SchemaSetupInterface $schemaSetup)
    {
    }

    /**
     * @return void
     */
    public function apply()
    {
        $setup = $this->schemaSetup;
        $setup->startSetup();
        $connection = $setup->getConnection();
        $tableName = $setup->getTable('tweakwise_attribute_slug');

        $connection->dropIndex($tableName, 'PRIMARY');

        $connection->addColumn($tableName, 'id', [
            'type' => Table::TYPE_INTEGER,
            'nullable' => false,
            'identity' => true,
            'unsigned' => true,
            'comment' => 'ID',
            'primary' => true,
        ]);

        $connection->addColumn($tableName, 'store_id', [
            'type' => Table::TYPE_INTEGER,
            'nullable' => false,
            'unsigned' => true,
            'comment' => 'store id',
            'default' => 0,
        ]);

        $connection->addIndex($tableName, 'ATTRIBUTE', ['attribute', 'store_id'], 'unique');

        $setup->endSetup();
    }

    /**
     * @return string[]
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @return string[]
     */
    public function getAliases()
    {
        return [];
    }
}
