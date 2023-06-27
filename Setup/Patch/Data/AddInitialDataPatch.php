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
class AddInitialDataPatch implements DataPatchInterface
{
	/**
	 * @var ModuleDataSetupInterface
	 */
	private ModuleDataSetupInterface $moduleDataSetup;

    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

	/**
	 * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
	 */
	public function __construct(
		ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
	)
	{
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
		$this->moduleDataSetup->getConnection()->startSetup();

        $table = $this->moduleDataSetup->getConnection()->getTableName('core_config_data');

        //rename settings from old module
        $this->moduleDataSetup->getConnection()->query('update '. $table .' SET value = "Tweakwise\\\\Magento2Tweakwise\\\\Model\\\\Catalog\\\\Layer\\\\Url\\\\Strategy\\\\QueryParameterStrategy" WHERE value = "Emico\\\\Tweakwise\\\\Model\\\\Catalog\\\\Layer\\\\Url\\\\Strategy\\\\QueryParameterStrategy"');
        $this->moduleDataSetup->getConnection()->query('update '. $table .' SET value = "Tweakwise\\\\Magento2Tweakwise\\\\Model\\\\Catalog\\\\Layer\\\\Url\\\\Strategy\\\\PathSlugStrategy" WHERE value = "Emico\\\\Tweakwise\\\\Model\\\\Catalog\\\\Layer\\\\Url\\\\Strategy\\\\PathSlugStrategy"');

        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $this->ensureCrosssellTemplateAttribute($eavSetup);
        $this->ensureUpsellTemplateAttribute($eavSetup);
        $this->ensureFeaturedTemplateAttribute($eavSetup);

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

    protected function ensureCrosssellTemplateAttribute(EavSetup $eavSetup)
    {
        foreach ([Category::ENTITY, Product::ENTITY] as $entityType) {
            $eavSetup->addAttribute($entityType, Config::ATTRIBUTE_CROSSSELL_TEMPLATE, [
                'type' => 'int',
                'label' => 'Crosssell template',
                'input' => 'select',
                'required' => false,
                'sort_order' => 10,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Tweakwise',
                'source' => 'Tweakwise\Magento2Tweakwise\Model\Config\Source\RecommendationOption\Product',
            ]);

            $eavSetup->addAttribute($entityType, Config::ATTRIBUTE_CROSSSELL_GROUP_CODE, [
                'type' => 'varchar',
                'label' => 'Crosssell group code',
                'input' => 'text',
                'required' => false,
                'sort_order' => 10,
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
                'sort_order' => 10,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Tweakwise',
                'source' => 'Tweakwise\Magento2Tweakwise\Model\Config\Source\RecommendationOption\Product',
            ]);

            $eavSetup->addAttribute($entityType, Config::ATTRIBUTE_UPSELL_GROUP_CODE, [
                'type' => 'varchar',
                'label' => 'Upsell group code',
                'input' => 'text',
                'required' => false,
                'sort_order' => 10,
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
            'sort_order' => 10,
            'global' => ScopedAttributeInterface::SCOPE_STORE,
            'group' => 'Tweakwise',
            'source' => 'Tweakwise\Magento2Tweakwise\Model\Config\Source\RecommendationOption\Featured',
        ]);
    }
}
