<?php

/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Model\Client\Type\FacetType;

use Tweakwise\Magento2Tweakwise\Model\Client\Type\Type;

/**
 * @method int getFacetId();
 * @method string getTitle();
 * @method int getSource();
 */
class SettingsType extends Type
{
    /**
     * Source type from attributes
     */
    public const SOURCE_CATEGORY = 'CATEGORY';
    public const SOURCE_FEED = 'FEED';

    /**
     * Tweakwise selection types
     */
    public const SELECTION_TYPE_LINK = 'link';
    public const SELECTION_TYPE_SLIDER = 'slider';
    public const SELECTION_TYPE_CHECKBOX = 'checkbox';
    public const SELECTION_TYPE_COLOR = 'color';
    public const SELECTION_TYPE_TREE = 'tree';

    /**
     * @return string
     */
    public function getUrlKey()
    {
        return (string) $this->getDataValue('urlkey');
    }

    /**
     * This returns the original (magento) attribute this facet is associated with
     * @return string
     */
    public function getAttributename()
    {
        return (string) $this->getDataValue('attributename');
    }

    /**
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getIsMultipleSelect()
    {
        return $this->getDataValue('ismultiselect') == 'true';
    }

    /**
     * @return string
     */
    public function getSelectionType()
    {
        return (string) $this->getDataValue('selectiontype');
    }

    /**
     * @return string
     */
    public function getIsNumberOfResultVisible()
    {
        return $this->getDataValue('isnrofresultsvisible') == 'true';
    }

    /**
     * @return bool
     */
    public function isPrice()
    {
        return $this->getUrlKey() === 'price';
    }

    /**
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getIsCollapsible()
    {
        return $this->getDataValue('iscollapsible') == 'true';
    }

    /**
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getIsCollapsed()
    {
        return $this->getDataValue('iscollapsed') == 'true';
    }

    /**
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getIsInfoVisible()
    {
        return $this->getDataValue('isinfovisible') == 'true';
    }

    /**
     * @return int
     */
    public function getNumberOfColumns()
    {
        return (int) $this->getDataValue('nrofcolumns');
    }

    /**
     * @return int
     */
    public function getNumberOfShownAttributes()
    {
        return (int) $this->getDataValue('nrofshownattributes');
    }

    /**
     * @return string|null
     */
    public function getInfoText()
    {
        $infoText = $this->getDataValue('infotext');
        if (!empty($infoText)) {
            return (string) $infoText;
        }

        return null;
    }

    /**
     * @return string
     */
    public function getExpandText()
    {
        return (string) $this->getDataValue('expandtext');
    }

    /**
     * @return string
     */
    public function getCollapseText()
    {
        return (string) $this->getDataValue('collapsetext');
    }

    /**
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getIsVisible()
    {
        return $this->getDataValue('isvisible') == 'true';
    }

    /**
     * @return int
     */
    public function getMultiSelectLogic()
    {
        return (int) $this->getDataValue('multiselectlogic');
    }

    /**
     * @return null|string
     */
    public function getPrefix()
    {
        $prefix = $this->getDataValue('prefix');
        if (!empty($prefix)) {
            return (string) $prefix;
        }

        return null;
    }

    /**
     * @return null|string
     */
    public function getPostfix()
    {
        $postfix = $this->getDataValue('postfix');
        if (!empty($postfix)) {
            return (string) $postfix;
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function getCssClass()
    {
        $cssClass = $this->getDataValue('cssclass');
        if (!empty($cssClass)) {
            return (string) $cssClass;
        }

        return null;
    }

    /**
     * @return bool
     */
    public function isSearchable()
    {
        return ($this->getBoolValue('issearchable'));
    }

    /**
     * @return mixed|string|null
     */
    public function getSearchPlaceholder()
    {
        return empty($this->getValue('searchplaceholder')) ? '' : $this->getValue('searchplaceholder');
    }

    /**
     * @return mixed|string|null
     */
    public function getSearchNoResultsText()
    {
        return empty($this->getValue('searchnoresultstext')) ? '' : $this->getValue('searchnoresultstext');
    }
}
