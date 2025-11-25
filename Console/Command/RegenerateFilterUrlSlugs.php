<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Console\Command;

use Exception;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\Strategy\FilterSlugManager;
use Magento\Store\Model\StoreManagerInterface;
use Tweakwise\Magento2TweakwiseExport\Model\ProductAttributes;

class RegenerateFilterUrlSlugs extends Command
{
    private const ENTITY_TYPE = 'catalog_product';

    /**
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param AttributeRepositoryInterface $attributeRepository
     * @param FilterSlugManager $filterSlugManager
     * @param StoreManagerInterface $storeManager
     * @param ProductAttributes $productAttributes
     */
    public function __construct(
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly AttributeRepositoryInterface $attributeRepository,
        private readonly FilterSlugManager $filterSlugManager,
        private readonly StoreManagerInterface $storeManager,
        private readonly ProductAttributes $productAttributes,
    ) {
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('tweakwise:regenerate:filter-url-slugs')
            ->setDescription('Regenerate url slugs for filter options.');
        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Start regenerating filter url slugs</info>');

        //empty existing slugs
        $this->filterSlugManager->truncateSlugTable();

        $counter = 0;
        foreach ($this->getAttributes() as $attribute) {
            try {
                $output->writeln(
                    sprintf('Regenerating urls for attribute %s (%s)', $attribute->getName(), $attribute->getId())
                );

                foreach ($this->storeManager->getStores() as $store) {
                    $attribute->setStoreId((int)$store->getId());
                    foreach ($attribute->getOptions() as $option) {
                        if (empty($option['value'])) {
                            continue;
                        }

                        $output->writeln(
                            sprintf(
                                ' - Regenerating url for option %s (%s), store id %d',
                                $option['label'],
                                $option['value'],
                                $store->getId(),
                            )
                        );

                        $this->regenerateSlugsForAttributeOption($attribute, $option, (int)$store->getId());

                        $counter++;
                    }
                }
            } catch (Exception $e) {
                $output->writeln(
                    sprintf(
                        '<error>Couldn\'t regenerate a filter url for %s (%d)%s%s</error>',
                        $attribute->getName(),
                        $attribute->getId(),
                        PHP_EOL,
                        $e->getMessage()
                    )
                );
            }
        }

        $output->writeln(
            sprintf('<info>Finished regenerating. Regenerated %d filter url slugs.</info>', $counter)
        );

        return Cli::RETURN_SUCCESS;
    }

    /**
     * @return iterable
     */
    protected function getAttributes(): iterable
    {
        $allowedTypes = ['select', 'multiselect', 'text'];
        $searchCriteria = $this->searchCriteriaBuilder->create();

        $items = $this->attributeRepository
            ->getList(self::ENTITY_TYPE, $searchCriteria)
            ->getItems();

        return array_filter($items, function ($attribute) {
            return $this->productAttributes->shouldExportAttribute($attribute);
        });
    }

    /**
     * @param \Magento\Eav\Api\Data\AttributeInterface $attribute
     * @param array $option
     * @param int $storeId
     * @return void
     */
    protected function regenerateSlugsForAttributeOption($attribute, $option, $storeId): void
    {
        $this->filterSlugManager->createFilterSlugByOption($attribute, $option, $storeId);
    }
}
