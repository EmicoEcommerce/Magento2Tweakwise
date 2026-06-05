<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Model\Client;

use Tweakwise\Magento2Tweakwise\Model\Client\Type\ItemType;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\Type;
use Tweakwise\Magento2TweakwiseExport\Model\Helper;

class Response extends Type
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * Response constructor.
     *
     * @param Helper $helper
     * @param Request $request
     * @param array $data
     */
    public function __construct(Helper $helper, Request $request, ?array $data = null)
    {
        $this->request = $request;
        $this->helper = $helper;
        parent::__construct($data);
    }

    /**
     * Function to get items from groups and set the items
     * @param array $groups
     * @param bool $addSimple
     * @return $this
     */
    public function setGroups(array $groups, bool $addSimple = true): self
    {
        if (!$groups) {
            $this->setItems([]);
            return $this;
        }

        $items = [];
        $groups = $this->normalizeArray($groups, 'group');
        foreach ($groups as $group) {
            array_push($items, ...$this->getGroupItems($group, $addSimple));
        }

        $this->setItems($items);
        return $this;
    }

    /**
     * @param array $group
     * @param bool $addSimple
     * @return array[]
     */
    protected function getGroupItems(array $group, bool $addSimple = true): array
    {
        $simple = $this->getMostSuitableVariant($group);
        $configurable = $this->getConfigurable($group);

        if (!$configurable) {
            return [$simple];
        }

        $items = [$this->applySimpleDataToConfigurable($configurable, $simple)];
        if ($addSimple && $this->shouldAppendSimpleItem($simple)) {
            $items[] = $simple;
        }

        return $items;
    }

    /**
     * @param array $configurable
     * @param array $simple
     * @return array
     */
    protected function applySimpleDataToConfigurable(array $configurable, array $simple): array
    {
        if (!empty($simple['image'])) {
            $configurable['image'] = $simple['image'];
        }

        if (!empty($simple['type'])) {
            $configurable['type'] = $simple['type'];

            // Add visual url to visual item
            if ($simple['type'] === 'visual') {
                $configurable['url'] = $simple['url'];
            }
        }

        if (!empty($simple['itemno'])) {
            $configurable['tw_id'] = (int)substr($simple['itemno'], 5);
        }

        return $configurable;
    }

    /**
     * @param array $simple
     * @return bool
     */
    protected function shouldAppendSimpleItem(array $simple): bool
    {
        return isset($simple['type']) && $simple['type'] === 'product';
    }

    /**
     * Function to get most suitable variant. This is always the first item in the array.
     * @param array $group
     * @return array
     */
    protected function getMostSuitableVariant(array $group): array
    {
        if (isset($group['items']['item'][0])) {
            return reset($group['items']['item']);
        }

        return $group['items']['item'];
    }

    /**
     * @param array $group
     * @return array
     */
    protected function getConfigurable(array $group): array
    {
        $configurable = ['itemno' => $group['code']];

        if (isset($group[ItemType::VISUAL_PROPERTIES])) {
            $configurable[ItemType::VISUAL_PROPERTIES] = $group[ItemType::VISUAL_PROPERTIES];
        }

        return $configurable;
    }

    /**
     * @param ItemType[]|array[] $items
     * @return $this
     */
    public function setItems(array $items): self
    {
        $items = $this->normalizeArray($items, 'item');

        $values = [];
        foreach ($items as $value) {
            if (!$value instanceof ItemType) {
                $value = new ItemType($value);
            }

            $values[] = $value;
        }

        $this->data['items'] = $values;
        return $this;
    }
}
