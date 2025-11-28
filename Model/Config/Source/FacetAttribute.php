<?php

/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Model\Config\Source;

use Tweakwise\Magento2Tweakwise\Exception\ApiException;
use Tweakwise\Magento2Tweakwise\Model\Client;
use Tweakwise\Magento2Tweakwise\Model\Client\RequestFactory;
use Magento\Framework\Data\OptionSourceInterface;

class FacetAttribute implements OptionSourceInterface
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var RequestFactory
     */
    protected $requestFactory;

    /**
     * @var array
     */
    protected $options;

    /**
     * Template constructor.
     *
     * @param Client $client
     * @param RequestFactory $requestFactory
     */
    public function __construct(Client $client, RequestFactory $requestFactory)
    {
        $this->client = $client;
        $this->requestFactory = $requestFactory;
    }

    /**
     * @return array
     */
    protected function buildOptions()
    {
        $request = $this->requestFactory->create();
        /** @var FacetAttributes $response */
        $response = $this->client->request($request);
        foreach ($response->getAttributes() as $attribute) {
            $result[] = ['value' => $attribute['title'], 'label' => $attribute['title']];
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        if (!$this->options) {
            try {
                $options = $this->buildOptions();
            } catch (ApiException $e) {
                $options = [];
            }

            $this->options = $options;
        }

        return $this->options;
    }
}
