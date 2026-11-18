<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\LocalizedException;

class Timeout extends Value
{
    public function beforeSave()
    {
        if (filter_var($this->getValue(), FILTER_VALIDATE_INT) === false || (int)$this->getValue() < 1) {
            throw new LocalizedException(__('API timeout must be a positive integer number of seconds.'));
        }

        return parent::beforeSave();
    }
}
