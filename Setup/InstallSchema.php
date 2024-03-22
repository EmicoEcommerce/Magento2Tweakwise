<?php // phpcs:disable Magento2.Legacy.InstallUpgrade.ObsoleteInstallSchemaScript

namespace Tweakwise\Magento2Tweakwise\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Tweakwise\Magento2Tweakwise\Api\Data\AttributeSlugInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * Upgrades DB schema for a module
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     * @throws \Zend_Db_Exception
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        $tableName = 'tweakwise_attribute_slug';
        if (!$installer->tableExists($tableName)) {
            $table = $installer->getConnection()
                ->newTable($installer->getTable($tableName))
                ->addColumn(
                    AttributeSlugInterface::ATTRIBUTE,
                    Table::TYPE_TEXT,
                    255,
                    [
                        'nullable' => false,
                        'primary' => true,
                        'unsigned' => true,
                    ],
                    'Attribute code'
                )->addColumn(
                    AttributeSlugInterface::SLUG,
                    Table::TYPE_TEXT,
                    255,
                    [
                        'nullable' => false,
                        'primary' => false,
                        'unsigned' => true,
                    ],
                    'URL Slug'
                );

            $table->addIndex(
                $installer->getIdxName($tableName, [AttributeSlugInterface::SLUG]),
                [AttributeSlugInterface::SLUG],
                AdapterInterface::INDEX_TYPE_UNIQUE
            );

            $installer->getConnection()->createTable($table);
        }

        $installer->endSetup();
    }
}
