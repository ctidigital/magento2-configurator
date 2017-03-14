<?php

namespace CtiDigital\Configurator\Model\Component;

use CtiDigital\Configurator\Model\Exception\ComponentException;
use CtiDigital\Configurator\Model\LoggingInterface;
use Magento\Cms\Api\Data\BlockInterfaceFactory;
use Magento\Framework\ObjectManagerInterface;

class Blocks extends YamlComponentAbstract
{

    protected $alias = 'blocks';
    protected $name = 'Blocks';
    protected $description = 'Component to create/maintain blocks.';

    /**
     * @var BlockInterfaceFactory
     */
    protected $blockFactory;

    /**
     * @var \Magento\Store\Model\Store\Interceptor
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchBuilder;

    /**
     * Blocks constructor.
     * @param LoggingInterface $log
     * @param ObjectManagerInterface $objectManager
     * @param BlockInterfaceFactory $blockFactory
     */
    public function __construct(
        LoggingInterface $log,
        ObjectManagerInterface $objectManager,
        BlockInterfaceFactory $blockFactory
    ) {

        parent::__construct($log, $objectManager);

        $this->blockFactory = $blockFactory;
        $this->storeManager = $this->objectManager->create(\Magento\Store\Model\Store::class);

    }

    /**
     * @param array $data
     */
    protected function processData(array $data = null)
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

            // Loop through the block data
            foreach ($blockData['block'] as $data) {

                $this->log->logComment(sprintf("Checking for existing blocks with identifier '%s'", $identifier));

                // Load a collection blocks
                $blocks = $this->blockFactory->create()->getCollection()->addFieldToFilter('identifier', $identifier);

                // Set initial vars
                $canSave = false;
                $block = null;

                // Check if there are existing blocks
                if ($blocks->count()) {
                    $stores = array();

                    // Check if stores are specified
                    if (isset($data['stores'])) {
                        $stores = $data['stores'];
                    }

                    // Find the exact block to process
                    $block = $this->getBlockToProcess($identifier, $blocks, $stores);
                }

                // If there is still no block to play with, create a new block object.
                if (is_null($block)) {
                    $block = $this->blockFactory->create();
                    $block->setIdentifier($identifier);
                    $canSave = true;
                }

                // Loop through each attribute of the data array
                foreach ($data as $key => $value) {

                    // Check if content is from a file source
                    if ($key == "source") {
                        $key = 'content';
                        //TODO load this with Magento's code, and also check for file existing
                        $value = file_get_contents(BP . '/' . $value);
                    }

                    // Skip stores
                    if ($key == "stores") {
                        continue;
                    }

                    // Log the old value if any
                    $this->log->logComment(sprintf(
                        "Checking block %s, key %s => %s",
                        $identifier . ' (' . $block->getId() . ')',
                        $key,
                        $block->getData($key)
                    ), 1);

                    // Check if there is a difference in value
                    if ($block->getData($key) != $value) {

                        // If there is, allow the block to be saved
                        $canSave = true;
                        $block->setData($key, $value);

                        $this->log->logInfo(sprintf(
                            "Set block %s, key %s => %s",
                            $identifier . ' (' . $block->getId() . ')',
                            $key,
                            $value
                        ), 1);
                    }
                }

                // Process stores
                // @todo compare stores to see if a save is required
                $block->setStoreId(0);
                if (isset($data['stores'])) {
                    $block->unsetData('store_id');
                    $block->unsetData('store_data');
                    $stores = array();
                    foreach ($data['stores'] as $code) {
                        $stores[] = $this->getStoreByCode($code)->getId();
                    }
                    $block->setStores($stores);
                }

                // If we can save the block
                if ($canSave) {
                    $block->save();
                    $this->log->logInfo(sprintf(
                        "Save block %s",
                        $identifier . ' (' . $block->getId() . ')'
                    ));
                }

            }
        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }
    }


    /**
     * Find the block to process given the identifier, block collection and optionally stores
     *
     * @param String $identifier
     * @param \Magento\Cms\Model\ResourceModel\Block\Collection $blocks
     * @param array $stores
     * @return \Magento\Cms\Model\Block|null
     */
    private function getBlockToProcess(
        $identifier,
        \Magento\Cms\Model\ResourceModel\Block\Collection $blocks,
        $stores = array()
    ) {

        // If there is only 1 block and stores hasn't been specified
        if ($blocks->count() == 1 && count($stores) == 0) {

            // Return that one block
            return $blocks->getFirstItem();
        }

        // If we do have stores specified
        if (count($stores) > 0) {

            // Use first store as filter to get the block ID.
            // Ideally, we would want to do something more intelligent here.
            $store = $this->getStoreByCode($stores[0]);
            $blocks = $this->blockFactory->create()->getCollection()
                ->addStoreFilter($store, false)
                ->addFieldToFilter('identifier', $identifier);

            // We should have no more than 1 block unless something funky is happening. Return the first block anyway.
            if ($blocks->count() >= 1) {
                return $blocks->getFirstItem();
            }
        }

        // In all other scenarios, return null as we can't find the block.
        return null;
    }

    /**
     * @param String $code
     * @return \Magento\Store\Model\Store
     */
    private function getStoreByCode($code)
    {

        // Load the store object
        $store = $this->storeManager->load($code, 'code');

        // Check if we get back a store ID.
        if (!$store->getId()) {

            // If not, stop the process by throwing an exception
            throw new ComponentException(sprintf("No store with code '%s' found", $code));
        }

        return $store;
    }
}
