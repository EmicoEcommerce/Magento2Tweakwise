<?php

namespace Tweakwise\Magento2Tweakwise\Setup\Patch\Data;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Magento\Eav\Setup\EavSetupFactory;
use Tweakwise\Magento2Tweakwise\Setup\Patch\AddRecommendationCategoryFieldsPatch;

/**
 * Patch is mechanism, that allows to do atomic upgrade data changes.
 */
class UpdateAttributeNamesPatch implements DataPatchInterface
{
	/**
	 * @var ModuleDataSetupInterface
	 */
	private ModuleDataSetupInterface $moduleDataSetup;

	/**
	 * @param ModuleDataSetupInterface $moduleDataSetup
	 */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

	/**
	 * Do Upgrade.
	 *
	 * @return void
	 */
	public function apply()
	{
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

		$this->moduleDataSetup->getConnection()->startSetup();

		$this->updateCrosssellTemplateAttribute($eavSetup);
        $this->updateShoppingcartCrosssellTemplateAttribute($eavSetup);

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
		return [
            \Tweakwise\Magento2Tweakwise\Setup\Patch\AddRecommendationCategoryFieldsPatch::class,
        ];
	}

    protected function updateCrosssellTemplateAttribute(EavSetup $eavSetup)
    {
        foreach ([Category::ENTITY, Product::ENTITY] as $entityType) {
            $eavSetup->updateAttribute($entityType, Config::ATTRIBUTE_CROSSSELL_TEMPLATE, 'label', 'Related products');

            $eavSetup->updateAttribute($entityType, Config::ATTRIBUTE_CROSSSELL_GROUP_CODE, 'label', 'Related products group code');
        }
    }

    protected function updateShoppingcartCrosssellTemplateAttribute(EavSetup $eavSetup)
    {
        foreach ([Category::ENTITY, Product::ENTITY] as $entityType) {
            $eavSetup->updateAttribute($entityType, Config::ATTRIBUTE_SHOPPINGCART_CROSSSELL_TEMPLATE, 'label', 'Crosssell template');

            $eavSetup->updateAttribute($entityType, Config::ATTRIBUTE_SHOPPINGCART_CROSSSELL_GROUP_CODE, 'label', 'Crosssell group code');
        }
    }
}
