<?php

namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Api\ComponentInterface;
use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Exception\ComponentException;
use Magento\Store\Model\Group;
use Magento\Store\Model\GroupFactory;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreFactory;
use Magento\Store\Model\Website;
use Magento\Store\Model\WebsiteFactory;
use Magento\Indexer\Model\IndexerFactory;
use Magento\Framework\Event\ManagerInterface;

class Websites implements ComponentInterface
{
    protected $alias = 'websites';
    protected $name = 'Websites';
    protected $description = 'Component to manage Websites, Stores and Store Views';
    protected $indexer;
    protected $reindex = false;
    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @var WebsiteFactory
     */
    protected $websiteFactory;

    /**
     * @var StoreFactory
     */
    protected $storeFactory;

    /**
     * @var GroupFactory
     */
    protected $groupFactory;

    /**
     * @var LoggerInterface
     */
    private $log;

    /**
     * Websites constructor.
     * @param IndexerFactory $indexerFactory
     * @param ManagerInterface $eventManager
     * @param WebsiteFactory $websiteFactory
     * @param StoreFactory $storeFactory
     * @param GroupFactory $groupFactory
     * @param LoggerInterface $log
     */
    public function __construct(
        IndexerFactory $indexerFactory,
        ManagerInterface $eventManager,
        WebsiteFactory $websiteFactory,
        StoreFactory $storeFactory,
        GroupFactory $groupFactory,
        LoggerInterface $log
    ) {
        $this->indexer = $indexerFactory;
        $this->eventManager = $eventManager;
        $this->websiteFactory = $websiteFactory;
        $this->storeFactory = $storeFactory;
        $this->groupFactory = $groupFactory;
        $this->log = $log;
    }

    public function execute($data = null)
    {
        try {
            if (!isset($data['websites'])) {
                throw new ComponentException("No websites found.");
            }

            // Loop through the websites
            foreach ($data['websites'] as $code => $websiteData) {
                // Process the website
                $website = $this->processWebsite($code, $websiteData);

                // Loop through the store groups
                foreach ($websiteData['store_groups'] as $storeGroupData) {
                    // Process the store group
                    $storeGroup = $this->processStoreGroup($storeGroupData, $website);

                    // Loop through the store views
                    foreach ($storeGroupData['store_views'] as $code => $storeViewData) {
                        // Process the store view
                        $this->processStoreView($code, $storeViewData, $storeGroup);
                    }

                    // As the store may not be created yet, associated the default store to the store group
                    // has to be completed after all stores for the store group have been created.
                    $this->setDefaultStore($storeGroup, $storeGroupData);
                }
            }

            if ($this->reindex === true) {
                $this->log->logInfo('Running a reindex of the catalog_product_price table.');
                $indexProcess = $this->indexer->create();
                $indexProcess->load('catalog_product_price');
                $indexProcess->reindexAll();
            }
        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }
    }

    /**
     * @param string $code
     * @param array $websiteData
     * @return Website
     * @SuppressWarnings(PHPMD)
     */
    protected function processWebsite($code, $websiteData)
    {
        $logNest = 1;

        try {
            $this->log->logComment(sprintf("Does the website with code '%s' already exist?", $code), $logNest);

            $website = $this->websiteFactory->create();
            $website->load($code, 'code');

            $canSave = false;

            // Check if it exists
            if ($website->getId()) {
                $this->log->logComment(sprintf("Website already exists with code '%s'", $code), $logNest);
            } else {
                $this->reindex = true;
                // If it does not exist, just set the existing data up with the website
                $canSave = true;
                $this->log->logComment(sprintf("Creating a new Website with code '%s'", $code), $logNest);
                $website->setData($websiteData);
                $website->setCode($code);
            }

            // Loop through other website data attributes
            foreach ($website->getData() as $key => $value) {
                // Skip any array based values (likely to be passed from new website creation)
                if (is_array($value)) {
                    continue;
                }

                // Check if the data from the source has the data and that is different to that in magento
                if (isset($websiteData[$key]) && $websiteData[$key] != $value) {
                    // Set the new data
                    $this->log->logInfo(
                        sprintf("Change '%s' from '%s' to '%s'", $key, $value, $websiteData[$key]),
                        $logNest
                    );
                    $website->setData($key, $websiteData[$key]);
                    $canSave = true;
                } else {
                    // Skip setting the data
                    if ($website->getId()) {
                        $this->log->logComment(sprintf("No change for '%s' - '%s'", $key, $value), $logNest);
                    } else {
                        $this->log->logInfo(sprintf("New setting for '%s' - '%s'", $key, $value), $logNest);
                    }
                }
            }

            if ($canSave) {
                // Save the website
                $website->getResource()->save($website);
                $this->log->logInfo(sprintf("Saved website '%s'", $code, $logNest));
            }
            return $website;
        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage(), $logNest);
        }
    }

    /**
     * @param array $storeGroupData
     * @param Website $website
     * @return Group
     * @SuppressWarnings(PHPMD)
     */
    protected function processStoreGroup($storeGroupData, Website $website)
    {
        $logNest = 2;

        try {
            if (isset($storeGroupData['group_id'])) {
                $this->log->logComment(
                    sprintf("Does the store group with id '%s' already exist?", $storeGroupData['group_id']),
                    $logNest
                );
            } else {
                $this->log->logComment(
                    sprintf("Does the store group with name '%s' already exist?", $storeGroupData['name']),
                    $logNest
                );
            }

            // Attempt to load the Store Group via the object manager
            $storeGroup = $this->groupFactory->create();

            if (isset($storeGroupData['group_id'])) {
                $storeGroup->load($storeGroupData['group_id']);
            } else {
                $storeGroup->load($storeGroupData['name'], 'name');
            }

            $canSave = false;

            // Check if the store group already exists
            if ($storeGroup->getId()) {
                $this->log->logComment(
                    sprintf("Store group already exists with name '%s'", $storeGroupData['name']),
                    $logNest
                );
            } else {
                // Create a new store group and set the basic data from source
                $this->log->logComment(
                    sprintf("Creating a new website with name '%s'", $storeGroupData['name']),
                    $logNest
                );
                $storeGroup->setData($storeGroupData);
                $storeGroup->setWebsite($website);
                $canSave = true;
                $this->reindex = true;
            }

            foreach ($storeGroup->getData() as $key => $value) {
                if (is_array($value)) {
                    continue;
                }
                // Set data if the data from source exists and is not the same as what is on magento's
                if (isset($storeGroupData[$key]) && $storeGroupData[$key] != $value) {
                    $this->log->logInfo(
                        sprintf("Change '%s' from '%s' to '%s'", $key, $value, $storeGroupData[$key]),
                        $logNest
                    );
                    $storeGroup->setData($key, $storeGroupData[$key]);
                    $canSave = true;
                } else {
                    if ($storeGroup->getId()) {
                        $this->log->logComment(sprintf("No change for '%s' - '%s'", $key, $value), $logNest);
                    } else {
                        $this->log->logInfo(sprintf("New setting for '%s' - '%s'", $key, $value), $logNest);
                    }
                }
            }

            if ($canSave) {
                // Save the store group
                $storeGroup->getResource()->save($storeGroup);
                $this->log->logInfo(sprintf("Saved store group '%s'", $storeGroup->getName()), $logNest);
            }

            return $storeGroup;
        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage(), $logNest);
        }
    }

    /**
     * @param $code
     * @param $storeViewData
     * @param Group $storeGroup
     * @return Store
     * @SuppressWarnings(PHPMD)
     */
    protected function processStoreView($code, $storeViewData, Group $storeGroup)
    {
        $logNest = 3;

        try {
            $this->log->logComment(sprintf("Does the website with code '%s' already exist?", $code), $logNest);

            $storeView = $this->storeFactory->create();
            $storeView->load($code, 'code');

            $canSave = false;

            // Check if it exists
            if ($storeView->getId()) {
                $this->log->logComment(sprintf("Store view already exists with code '%s'", $code), $logNest);
            } else {
                // If it does not exist, just set the existing data up with the store view
                $canSave = true;
                $this->reindex = true;
                $this->log->logComment(sprintf("Creating a new Website with code '%s'", $code), $logNest);
                $storeView->setData($storeViewData);
                $storeView->setCode($code);
            }

            // Check if the store group is the correct one
            if ($storeView->getStoreGroupId() != $storeGroup->getId()) {
                $this->log->logInfo(
                    sprintf(
                        "Setting new store group for store view '%s' from '%s' to '%s'",
                        $code,
                        $storeView->getStoreGroupId(),
                        $storeGroup->getId()
                    )
                );
                $storeView->setGroup($storeGroup);
                $canSave = true;
            }

            // Loop through other store view data attributes
            foreach ($storeView->getData() as $key => $value) {
                // Check if the data from the source has the data and that is different to that in magento
                if (isset($storeViewData[$key]) && $storeViewData[$key] != $value) {
                    // Set the new data
                    $this->log->logInfo(
                        sprintf("Change '%s' from '%s' to '%s'", $key, $value, $storeViewData[$key]),
                        $logNest
                    );
                    $storeView->setData($key, $storeViewData[$key]);
                    $canSave = true;
                } else {
                    // Skip setting the data
                    if ($storeView->getId()) {
                        $this->log->logComment(sprintf("No change for '%s' - '%s'", $key, $value), $logNest);
                    } else {
                        $this->log->logInfo(sprintf("New setting for '%s' - '%s'", $key, $value), $logNest);
                    }
                }
            }

            if ($canSave) {
                // Save the store view
                $storeView->getResource()->save($storeView);
                $this->eventManager->dispatch('store_add', ['store' => $storeView]);
                $this->log->logInfo(sprintf("Saved store view '%s'", $code), $logNest);
            }
            return $storeView;
        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage(), $logNest);
        }
    }

    /**
     * @param Group $storeGroup
     * @param $storeGroupData
     * @SuppressWarnings(PHPMD)
     */
    protected function setDefaultStore(Group $storeGroup, $storeGroupData)
    {
        $logNest = 2;

        try {
            $this->log->logComment(
                sprintf("Setting default store for the store group '%s", $storeGroup->getName()),
                $logNest
            );

            $storeView = $this->storeFactory->create();
            $storeView->load($storeGroupData['default_store'], 'code');

            if (!$storeView->getId()) {
                throw new ComponentException(
                    sprintf("Cannot find store view with code %s", $storeGroupData['default_store'])
                );
            }

            if ($storeView->getStoreGroupId() != $storeGroup->getId()) {
                throw new ComponentException(
                    sprintf(
                        "This store view code %s does not belong to %s",
                        $storeGroupData['default_store'],
                        $storeGroup->getName()
                    )
                );
            }

            // Figure out if it needs changing
            if ($storeGroup->getDefaultStoreId() == $storeView->getId()) {
                $this->log->logComment(
                    sprintf("No change with the default store for '%s", $storeGroup->getName()),
                    $logNest
                );
            } else {
                $storeGroup->setDefaultStoreId($storeView->getId());
                $storeGroup->getResource()->save($storeGroup);
                $this->log->logInfo(
                    sprintf(
                        "Set default store view '%s' for store group '%s",
                        $storeView->getCode(),
                        $storeGroup->getName()
                    ),
                    $logNest
                );
            }
        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage(), $logNest);
        }
    }
}
