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

    /**
     * @param $identifier
     * @param $blockData
     * @SuppressWarnings(PHPMD)
     */
    private function processBlock($identifier, $blockData)
    {
        try {

            foreach ($blockData['block'] as $data) {

                $this->log->logComment(sprintf("Checking for existing blocks with identifier '%s'", $identifier));

                $blocks = $this->blockFactory->create()->getCollection()->addFieldToFilter('identifier', $identifier);

                $canSave = false;

                if ($blocks->count()) {
                    if (!isset($data['stores'])) {
                        $stores = array();
                    }
                    $block = $this->getBlockToProcess($blocks, $stores);
                } else {
                    $block = $this->blockFactory->create();
                    $block->setIdentifier($identifier);
                    $canSave = true;
                }


                foreach ($data as $key => $value) {

                    // Check if content is from a file source
                    if ($key == "source") {
                        $key = 'content';
                        $value = file_get_contents(BP . '/' . $value);
                    }

                    // Log the old value if any
                    $this->log->logComment(sprintf(
                        "Checking block %s, key %s => %s",
                        $identifier . '(' . $block->getId() . ')',
                        $key,
                        $block->getData($key)
                    ));

                    // Check if there is a difference in value
                    if ($block->getData($key) != $value) {

                        $canSave = true;
                        $block->setData($key, $value);

                        $logValue = $value;

                        if (is_array($value)) {
                            $logValue = implode(",", $value);
                        }

                        $this->log->logInfo(sprintf(
                            "Set block %s, key %s => %s",
                            $identifier . '(' . $block->getId() . ')',
                            $key,
                            $logValue
                        ));
                    }
                }

                if (!isset($data['stores'])) {

                    $block->setStoreId(0);
                }

                if ($canSave) {
                    $block->save();
                    $this->log->logComment(sprintf(
                        "Save block %s",
                        $identifier . '(' . $block->getId() . ')'
                    ));
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
