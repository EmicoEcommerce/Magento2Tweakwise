<?php

/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Model\Client;

use Tweakwise\Magento2Tweakwise\Exception\InvalidArgumentException;
use Magento\Framework\ObjectManagerInterface;

class RequestFactory
{
    /**
     * Object Manager instance
     *
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var string
     */
    protected $type;

    /**
     * Factory constructor
     *
     * @param ObjectManagerInterface $objectManager
     * @param string $type
     */
    public function __construct(ObjectManagerInterface $objectManager, $type = Request::class)
    {
        $this->objectManager = $objectManager;
        $this->type = $type;
    }

    /**
     * Create class instance with specified parameters
     *
     * @param array $parameters
     * @return Request
     * @throws InvalidArgumentException
     */
    public function create(array $parameters = [])
    {
        $request =  $this->objectManager->create($this->type, ['parameters' => $parameters]);
        if (!$request instanceof Request) {
            throw new InvalidArgumentException(sprintf('%s is not an instanceof %s', $this->type, Request::class));
        }

        return $request;
    }
}
