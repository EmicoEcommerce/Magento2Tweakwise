<?php
/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Tweakwise\Magento2Tweakwise\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InstallData implements InstallDataInterface
{
    /**
     * @var EavSetupFactory
     */
    protected $eavSetupFactory;

    /**
     * @var WriterInterface
     */
    protected $writer;

    /**
     * UpgradeData constructor.
     *
     * @param EavSetupFactory $eavSetupFactory
     * @param WriterInterface $writer
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory,
        WriterInterface $writer
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->writer = $writer;
    }

    /**
     * {@inheritdoc}
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        //rename settings from old module
        $setup->getConnection()->query('update core_config_data SET value = "Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\Strategy\QueryParameterStrategy" WHERE value = "Emico\Tweakwise\Model\Catalog\Layer\Url\Strategy\QueryParameterStrategy"');
        $setup->getConnection()->query('update core_config_data SET value = "Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\Strategy\PathSlugStrategy" WHERE value = "Emico\Tweakwise\Model\Catalog\Layer\Url\Strategy\PathSlugStrategy"');


        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
        $this->ensureCrosssellTemplateAttribute($eavSetup);
        $this->ensureUpsellTemplateAttribute($eavSetup);
        $this->ensureFeaturedTemplateAttribute($eavSetup);
        $this->updateNavigatorBaseUrl();

        $setup->endSetup();
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

    /**
     * Update tw server url as the old url will be retired
     */
    protected function updateNavigatorBaseUrl()
    {
        $this->writer->save('tweakwise/general/server_url', 'https://gateway.tweakwisenavigator.com/');
    }
}
