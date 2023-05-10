<?php
/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Model\Client;

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
    public function __construct(Helper $helper, Request $request, array $data = null)
    {
        $this->request = $request;
        $this->helper = $helper;
        parent::__construct($data);
    }

    public function isSuccess()
    {
        $statusCode = (int)$this->getValue('httpStatusCode');

        return $statusCode >= 200 && $statusCode < 300;
    }

    public function isRetryable()
    {
        $statusCode = (int)$this->getValue('httpStatusCode');

        if ($statusCode >= 200 && $statusCode < 300)
            return false;

        if ($statusCode >= 400 && $statusCode < 500)
            return false;

        if ($statusCode == 500)
            return false;

        return (int)$this->getValue('isNetworkError');
    }
}
