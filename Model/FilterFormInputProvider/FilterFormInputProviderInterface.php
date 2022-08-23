<?php

namespace Tweakwise\Magento2Tweakwise\Model\FilterFormInputProvider;

interface FilterFormInputProviderInterface
{
    /**
     * Should return an array of hidden parameters which are added to src/view/frontend/templates/layer/view.phtml
     * This is needed for ajax filtering. The array should be formatted as 'name' => 'value'
     * name will be rendered as name attribute in an <input> tag and obviously value will be its value attribute
     *
     * @return string[]
     */
    public function getFilterFormInput();
}
