<?php

namespace Tweakwise\Magento2Tweakwise\Model\FilterFormInputProvider;

use Tweakwise\Magento2Tweakwise\Model\Config;
use Magento\Framework\App\Request\Http as MagentoHttpRequest;
use Magento\Framework\Encryption\Encryptor;

class HashInputProvider
{
    /**
     * @var MagentoHttpRequest
     */
    protected $request;

    /**
     * @var Encryptor $encryptor
     */
    protected $encryptor;

    /**
     * @var Config $config
     */
    protected $config;

    /**
     * ToolbarInputProvider constructor.
     * @param MagentoHttpRequest $request
     */
    public function __construct(MagentoHttpRequest $request, Config $config, Encryptor $encryptor)
    {
        $this->request = $request;
        $this->config = $config;
        $this->encryptor = $encryptor;
    }

    public function getHash($input)
    {
        $input['salt'] = $this->getSalt();

        return $this->encryptor->hash(serialize($input));
    }

    protected function getSalt()
    {
        return $this->config->getSalt();
    }

    public function validateHash($request)
    {
        $isValid = false;
        $hash = $request->getParam('__tw_hash', null);

        if (!empty($hash)) {
            $input['__tw_ajax_type'] = $request->getParam('__tw_ajax_type');
            $input['__tw_object_id'] = (int) $request->getParam('__tw_object_id');
            $input['__tw_original_url'] = $request->getParam('__tw_original_url');

            $originalHash = $this->getHash($input);

            if ($hash === $originalHash) {
                $isValid = true;
            }
        }

        return $isValid;
    }
}
