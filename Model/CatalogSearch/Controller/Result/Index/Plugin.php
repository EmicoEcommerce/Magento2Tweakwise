<?php

namespace Tweakwise\Magento2Tweakwise\Model\CatalogSearch\Controller\Result\Index;

use Tweakwise\Magento2Tweakwise\Model\Config;
use Magento\CatalogSearch\Controller\Result\Index;
use Magento\Search\Model\Query;
use Magento\Search\Model\QueryFactory;

class Plugin
{
    /**
     * @var Config Tweakwise Config object used to query search settings
     */
    protected $config;

    /**
     * @var QueryFactory
     */
    protected $queryFactory;

    /**
     * Plugin constructor.
     *
     * @param Config $config
     * @param QueryFactory $queryFactory
     */
    public function __construct(Config $config, QueryFactory $queryFactory)
    {
        $this->config = $config;
        $this->queryFactory = $queryFactory;
    }

    /**
     * If search is tweakwise search is enabled we do
     * not redirect to a magento redirect
     *
     * @param Index $subject
     *
     * @return mixed
     */
    public function beforeExecute(Index $subject)
    {
        if ($this->config->isSearchEnabled()) {
            /* @var Query $query */
            $query = $this->queryFactory->get();
            // Set redirect to '', so that it does not get executed
            $query->setRedirect('');
        }
    }
}
