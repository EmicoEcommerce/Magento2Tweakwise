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

/**
 * Patch is mechanism, that allows to do atomic upgrade data changes.
 */
class AddRecommendationCategoryFieldsPatch implements DataPatchInterface
{
	/**
	 * @var ModuleDataSetupInterface
	 */
	private ModuleDataSetupInterface $moduleDataSetup;

    /**
     * @var EavSetupFactory
     */
	private EavSetupFactory $eavSetupFactory;

	/**
	 * @param ModuleDataSetupInterface $moduleDataSetup
     * @param
	 */
	public function __construct(
		ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
	)
	{
        $this->eavSetupFactory = $eavSetupFactory;
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
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

		$this->ensureShoppingcartCrosssellTemplateAttribute($eavSetup);

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

    protected function ensureShoppingcartCrosssellTemplateAttribute(EavSetup $eavSetup)
    {
        foreach ([Category::ENTITY, Product::ENTITY] as $entityType) {
            $eavSetup->addAttribute($entityType, Config::ATTRIBUTE_SHOPPINGCART_CROSSSELL_TEMPLATE, [
                'type' => 'int',
                'label' => 'Shoppingcart crosssell template',
                'input' => 'select',
                'required' => false,
                'sort_order' => 50,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Tweakwise',
                'source' => 'Tweakwise\Magento2Tweakwise\Model\Config\Source\RecommendationOption\Product',
            ]);

            $eavSetup->addAttribute($entityType, Config::ATTRIBUTE_SHOPPINGCART_CROSSSELL_GROUP_CODE, [
                'type' => 'varchar',
                'label' => 'Shoppincart crosssell code',
                'input' => 'text',
                'required' => false,
                'sort_order' => 55,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Tweakwise',
            ]);
        }
    }

    protected function ensureCrosssellTemplateAttribute(EavSetup $eavSetup)
    {
        foreach ([Category::ENTITY, Product::ENTITY] as $entityType) {
            $eavSetup->addAttribute($entityType, Config::ATTRIBUTE_CROSSSELL_TEMPLATE, [
                'type' => 'int',
                'label' => 'Crosssell template',
                'input' => 'select',
                'required' => false,
                'sort_order' => 20,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Tweakwise',
                'source' => 'Tweakwise\Magento2Tweakwise\Model\Config\Source\RecommendationOption\Product',
            ]);

            $eavSetup->addAttribute($entityType, Config::ATTRIBUTE_CROSSSELL_GROUP_CODE, [
                'type' => 'varchar',
                'label' => 'Crosssell group code',
                'input' => 'text',
                'required' => false,
                'sort_order' => 25,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Tweakwise',
            ]);
        }
    }

    protected function ensureUpsellTemplateAttribute(EavSetup $eavSetup)
    {
        foreach ([Category::ENTITY, Product::ENTITY] as $entityType) {
            $eavSetup->addAttribute($entityType, Config::ATTRIBUTE_UPSELL_TEMPLATE, [
                'type' => 'int',
                'label' => 'Upsell template',
                'input' => 'select',
                'required' => false,
                'sort_order' => 30,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Tweakwise',
                'source' => 'Tweakwise\Magento2Tweakwise\Model\Config\Source\RecommendationOption\Product',
            ]);

            $eavSetup->addAttribute($entityType, Config::ATTRIBUTE_UPSELL_GROUP_CODE, [
                'type' => 'varchar',
                'label' => 'Upsell group code',
                'input' => 'text',
                'required' => false,
                'sort_order' => 35,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Tweakwise',
            ]);
        }
    }

    protected function ensureFeaturedTemplateAttribute(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(Category::ENTITY, Config::ATTRIBUTE_FEATURED_TEMPLATE, [
            'type' => 'int',
            'label' => 'Featured products template',
            'input' => 'select',
            'required' => false,
            'sort_order' => 60,
            'global' => ScopedAttributeInterface::SCOPE_STORE,
            'group' => 'Tweakwise',
            'source' => 'Tweakwise\Magento2Tweakwise\Model\Config\Source\RecommendationOption\Featured',
        ]);
    }
}
