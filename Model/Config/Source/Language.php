<?php

namespace Tweakwise\Magento2Tweakwise\Model\Config\Source;

use Tweakwise\Magento2Tweakwise\Exception\ApiException;
use Tweakwise\Magento2Tweakwise\Model\Client;
use Tweakwise\Magento2Tweakwise\Model\Client\RequestFactory;
use Tweakwise\Magento2Tweakwise\Model\Client\Response\Catalog\LanguageResponse;
use Magento\Framework\Data\OptionSourceInterface;

class Language implements OptionSourceInterface
{
    /**
     * @var RequestFactory
     */
    protected $requestFactory;

    /**
     * @var Client
     */
    protected $client;

    /**
     * Language constructor.
     * @param RequestFactory $requestFactory
     * @param Client $client
     */
    public function __construct(
        RequestFactory $requestFactory,
        Client $client
    ) {
        $this->requestFactory = $requestFactory;
        $this->client = $client;
    }

    /**
     * Return array of options as value-label pairs
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [
            [
                'label' => 'Don\'t use language in search',
                'value' => ''
            ]
        ];

        try {
            $request = $this->requestFactory->create();
            /** @var LanguageResponse $response */
            $response = $this->client->request($request);

            $languages = $response->getLanguages();

            foreach ($languages as $language) {
                $options[] = [
                    'label' => $language['name'],
                    'value' => $language['key']
                ];
            }
        } catch (ApiException $e) { }

        return $options;
    }
}
