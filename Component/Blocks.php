<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Api\ComponentInterface;
use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Exception\ComponentException;
use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Cms\Api\Data\BlockInterfaceFactory;
use Magento\Cms\Model\Block;
use Magento\Cms\Model\ResourceModel\Block\Collection;
use Magento\Framework\DataObject;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Store\Model\Store;

class Blocks implements ComponentInterface
{
    protected string $alias = 'blocks';
    protected string $name = 'Blocks';
    protected string $description = 'Component to create/maintain blocks.';

    public function __construct(
        protected readonly BlockInterfaceFactory $blockFactory,
        protected readonly BlockRepositoryInterface $blockRepository,
        protected readonly Store $storeManager,
        private readonly LoggerInterface $log,
        private readonly DriverInterface $driver
    ) {
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
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
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
                        $value = $this->driver->fileGetContents(BP . '/' . $value);
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

                // Process stores.
                // BlockRepository::save() contains: if (empty($block->getStoreId())) { setStoreId(currentStore) }
                // Both null and int 0 are "empty" in PHP, so setStoreId(0) or unsetData('store_id')
                // cause BlockRepository to silently overwrite store_id with the current store ID
                // (typically 1 = admin/default). That store ID is then used by getIsUniqueBlockToStores()
                // for its IN check, which finds the previous same-identifier block and throws.
                // Fix: always call setData('store_id', array) — a non-empty array is never "empty",
                // so BlockRepository leaves it alone. getIsUniqueBlockToStores reads getData('store_id')
                // and gets the correct IDs; Block::getStores() falls back to store_id for the SaveHandler.
                if (isset($data['stores'])) {
                    $stores = [];
                    foreach ($data['stores'] as $code) {
                        $stores[] = $this->getStoreByCode($code)->getId();
                    }
                } else {
                    $stores = [Store::DEFAULT_STORE_ID];
                }
                $block->setData('store_id', $stores);

                // If we can save the block
                if ($canSave) {
                    $this->blockRepository->save($block);
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
