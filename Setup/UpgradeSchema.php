<?php
namespace Tweakwise\Magento2Tweakwise\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $connection = $setup->getConnection();
        $tableName = $setup->getTable('tweakwise_attribute_slug');

        $connection->dropIndex($tableName, 'PRIMARY');

        $connection->addColumn('tweakwise_attribute_slug', 'id', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            'nullable' => false,
            'identity' => true,
            'unsigned' => true,
            'comment' => 'ID',
            'primary' => true,
        ]);

        $connection->addColumn('tweakwise_attribute_slug', 'store_id', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            'nullable' => false,
            'unsigned' => true,
            'comment' => 'store id',
            'default' => 0,
        ]);

        $connection->addIndex($tableName, 'ATTRIBUTE', ['attribute', 'store_id'], 'unique');

        $setup->endSetup();
    }
}
