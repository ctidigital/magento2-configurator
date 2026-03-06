<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Api\ComponentInterface;
use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Exception\ComponentException;
use Magento\Cms\Api\Data\BlockInterfaceFactory;
use Magento\Cms\Model\Block;
use Magento\Cms\Model\ResourceModel\Block\Collection;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\DataObject;
use Magento\Store\Model\Store;

class Blocks implements ComponentInterface
{
    protected string $alias = 'blocks';
    protected string $name = 'Blocks';
    protected string $description = 'Component to create/maintain blocks.';

    /**
     * @var BlockInterfaceFactory
     */
    protected BlockInterfaceFactory $blockFactory;

    /**
     * @var Store
     */
    protected Store $storeManager;

    /**
     * @var SearchCriteriaBuilder
     */
    protected SearchCriteriaBuilder $searchBuilder;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $log;

    /**
     * Blocks constructor.
     *
     * @param BlockInterfaceFactory $blockFactory
     * @param Store $store
     * @param LoggerInterface $log
     */
    public function __construct(
        BlockInterfaceFactory $blockFactory,
        Store $store,
        LoggerInterface $log
    ) {
        $this->blockFactory = $blockFactory;
        $this->storeManager = $store;
        $this->log = $log;
    }

    /**
     * Execute the component with the given block data.
     */
    public function execute(mixed $data = null): void
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
     * Process a single CMS block by identifier and block data.
     *
     * @SuppressWarnings(PHPMD)
     */
    private function processBlock(mixed $identifier, mixed $blockData): void
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
                    $stores = [];

                    // Check if stores are specified
                    if (isset($data['stores'])) {
                        $stores = $data['stores'];
                    }

                    // Find the exact block to process
                    $block = $this->getBlockToProcess($identifier, $blocks, $stores);
                }

                // If there is still no block to play with, create a new block object.
                if ($block === null) {
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
                        // phpcs:ignore Magento2.Functions.DiscouragedFunction
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
                    $stores = [];
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
     * Find the block to process given the identifier, block collection and optionally stores.
     *
     * @param string $identifier
     * @param Collection $blocks
     * @param array $stores
     * @return Block|DataObject|null
     */
    private function getBlockToProcess(
        string $identifier,
        Collection $blocks,
        array $stores = []
    ): Block|DataObject|null {
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
     * Load a store model by its code.
     */
    private function getStoreByCode(string $code): Store
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

    /**
     * Return the component alias.
     */
    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * Return the component description.
     */
    public function getDescription(): string
    {
        return $this->description;
    }
}
