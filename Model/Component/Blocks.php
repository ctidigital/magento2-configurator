<?php

namespace CtiDigital\Configurator\Model\Component;

use CtiDigital\Configurator\Model\Exception\ComponentException;
use CtiDigital\Configurator\Model\LoggingInterface;
use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Cms\Api\Data\BlockInterface;
use Magento\Cms\Api\Data\BlockInterfaceFactory;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\ObjectManagerInterface;
use Symfony\Component\Yaml\Yaml;

class Blocks extends ComponentAbstract
{

    protected $alias = 'blocks';
    protected $name = 'Blocks';
    protected $description = 'Component to create/maintain blocks.';

    /**
     * @var BlockRepositoryInterface
     */
    protected $blockRepository;

    /**
     * @var FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchBuilder;

    /**
     * Blocks constructor.
     * @param LoggingInterface $log
     * @param ObjectManagerInterface $objectManager
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param BlockRepositoryInterface $blockRepoInterface
     * @param BlockInterfaceFactory $blockInterfaceFactory
     * @SuppressWarnings(PHPMD)
     */
    public function __construct(
        LoggingInterface $log,
        ObjectManagerInterface $objectManager,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        BlockRepositoryInterface $blockRepoInterface,
        BlockInterfaceFactory $blockInterfaceFactory
    ) {

        $this->searchBuilder = $searchCriteriaBuilder;
        $this->blockRepository = $blockRepoInterface;

        parent::__construct($log, $objectManager);
    }

    protected function canParseAndProcess()
    {
        $path = BP . '/' . $this->source;
        if (!file_exists($path)) {
            throw new ComponentException(
                sprintf("Could not find file in path %s", $path)
            );
        }
        return true;
    }

    protected function parseData($source = null)
    {

        try {
            if ($source == null) {
                throw new ComponentException(
                    sprintf('The %s component requires to have a file source definition.', $this->alias)
                );
            }

            $parser = new Yaml();
            return $parser->parse(file_get_contents($source));
        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }
    }

    /**
     * @param array $data
     */
    protected function processData($data = null)
    {
        try {

            foreach ($data as $identifier => $data) {
                $this->processBlock($identifier, $data);
            }

        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }
    }

    private function processBlock($identifier, $blockData)
    {
        try {

            foreach ($blockData['block'] as $data) {

                $this->log->logInfo(sprintf("Checking for existing blocks with identifier '%s'", $identifier));

                $searchCriteria = $this->searchBuilder
                    ->addFilter('identifier', $identifier)
                    ->create();

                $blocks = $this->blockRepository->getList($searchCriteria);

                if ($blocks->getTotalCount()) {
                    if (isset($data['stores'])) {
                        $this->getBlockToProcess($blocks, $data['stores']);
                    }
                }

                print_r($data);
            }
        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }
    }


    private function getBlockToProcess(\Magento\Framework\Api\SearchResults $blocks, $stores)
    {
        //$items = $blocks->getItems();
        foreach ($blocks->getItems() as $block) {
            print_r($block);
            print_r($stores);
        }
    }
}
