<?php

namespace CtiDigital\Configurator\Model\Component;

use CtiDigital\Configurator\Model\Exception\ComponentException;
use CtiDigital\Configurator\Model\LoggingInterface;
use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Cms\Api\Data\BlockInterface;
use Magento\Cms\Api\Data\BlockInterfaceFactory;
use Magento\Cms\Model\BlockFactory;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\ObjectManagerInterface;
use Symfony\Component\Yaml\Yaml;

class Blocks extends ComponentAbstract
{

    protected $alias = 'blocks';
    protected $name = 'Blocks';
    protected $description = 'Component to create/maintain blocks.';

    /**
     * @var BlockFactory
     */
    protected $blockFactory;

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
        BlockFactory $blockFactory
    ) {

        $this->blockFactory = $blockFactory;
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

                $blocks = $this->blockFactory->create()->getCollection()->addFieldToFilter('identifier', $identifier);

                if ($blocks->count()) {
                    $block = $this->getBlockToProcess($blocks, $data['stores']);
                } else {
                    $block = $this->blockFactory->create();
                }

                foreach ($data as $key => $value) {
                    $this->log->logInfo(sprintf(
                        "Checking block %s, key %s => %s",
                        $identifier.'('.$block->getId().')',
                        $key,
                        $block->getData($key)
                    ));
                    if ($block->getData($key) != $value) {
                        $this->get
                    }
                }

                print_r($data);
            }
        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }
    }


    private function getBlockToProcess(\Magento\Cms\Model\ResourceModel\Block\Collection $blocks, $stores = array())
    {
        // If there is only 1 block and there are no stores specified
        if ($blocks->count() == 1 && empty($stores)) {

            // Return that one block
            return $blocks->getFirstItem();
        }

        foreach ($blocks->getItems() as $block) {
            print_r($block->getId());
        }
    }
}
