<?php

namespace Tweakwise\Magento2Tweakwise\Model\Client\Endpoints;

class EndpointContainer
{
    private const MAIN_ENDPOINT = 'https://gateway.tweakwisenavigator.net';
    private const FALLBACK_ENDPOINTS = ['https://gateway.tweakwisenavigator.com'];

    private $mainEindpoint;
    private $fallbackEndpoints;
    private $allEndpoints;

    public function __construct()
    {
        $this->mainEindpoint = new TwnEndpoint(self::MAIN_ENDPOINT);

        foreach (self::FALLBACK_ENDPOINTS as $endpoint)
        {
            $this->fallbackEndpoints[] = new TwnEndpoint($endpoint);
        }
    }

    public function getEndpoints()
    {
        $availableEndpoints = [];

        foreach ($this->allEndpoints() as $endpoint)
        {
            if($endpoint->isTryable()) {
                $availableEndpoints[] = $endpoint;
            }
        }

        if (!empty($availableEndpoints)) {
            return $availableEndpoints;
        }

        // when there are no tryable hosts reset and return them all
        foreach ($this->allEndpoints() as &$endpoint)
        {
            $endpoint->reset();
        }

        return $this->allEndpoints();
    }

    private function allEndpoints()
    {
        yield $this->mainEindpoint;

        foreach ($this->fallbackEndpoints as $fallbackEndpoint) {
            yield $fallbackEndpoint;
        }
    }
}
