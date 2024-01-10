<?php

namespace Tweakwise\Magento2Tweakwise\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Tweakwise\Magento2Tweakwise\Model\Config;

/**
 * Patch is mechanism, that allows to do atomic upgrade data changes.
 */
class UpdateLabelNamesPatch implements DataPatchInterface
{
	/**
	 * @var ModuleDataSetupInterface
	 */
	private ModuleDataSetupInterface $moduleDataSetup;

	/**
	 * @param ModuleDataSetupInterface $moduleDataSetup
	 */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
    }

	/**
	 * Do Upgrade.
	 *
	 * @return void
	 */
	public function apply()
	{
		$this->moduleDataSetup->getConnection()->startSetup();

        $table = $this->moduleDataSetup->getConnection()->getTableName('eav_attribute');

        //rename labels
        $this->moduleDataSetup->getConnection()->query('update '. $table .' SET frontend_label = "Related products" WHERE attribute_code = "' . Config::ATTRIBUTE_CROSSSELL_TEMPLATE .'"');
        $this->moduleDataSetup->getConnection()->query('update '. $table .' SET frontend_label = "Related products group code" WHERE attribute_code = "' . Config::ATTRIBUTE_CROSSSELL_GROUP_CODE .'"');

        $this->moduleDataSetup->getConnection()->query('update '. $table .' SET frontend_label = "Crosssell template" WHERE attribute_code = "' . Config::ATTRIBUTE_SHOPPINGCART_CROSSSELL_TEMPLATE .'"');
        $this->moduleDataSetup->getConnection()->query('update '. $table .' SET frontend_label = "Crosssell group code" WHERE attribute_code = "' . Config::ATTRIBUTE_SHOPPINGCART_CROSSSELL_GROUP_CODE .'"');

        $this->moduleDataSetup->getConnection()->endSetup();
	}

	/**
	 * Get aliases (previous names) for the patch.
	 *
	 * @return string[]
	 */
	public function getAliases()
	{
		return [];
	}

	/**
	 * Get array of patches that have to be executed prior to this.
	 *
	 * Example of implementation:
	 *
	 * [
	 *      \Vendor_Name\Module_Name\Setup\Patch\Patch1::class,
	 *      \Vendor_Name\Module_Name\Setup\Patch\Patch2::class
	 * ]
	 *
	 * @return string[]
	 */
	public static function getDependencies()
	{
		return [];
	}
}
