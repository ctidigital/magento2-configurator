<?php

namespace CtiDigital\Configurator\Model\Component;

use CtiDigital\Configurator\Model\Exception\ComponentException;
use Magento\Store\Model\Group;
use Magento\Store\Model\GroupFactory;
use Magento\Store\Model\StoreFactory;
use Magento\Store\Model\Website;
use Magento\Store\Model\WebsiteFactory;
use Symfony\Component\Yaml\Yaml;

class Websites extends ComponentAbstract
{

    protected $alias = 'websites';
    protected $name = 'Websites';
    protected $description = 'Component to manage Websites, Stores and Store Views';

    /**
     * @return bool
     */
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

    /**
     * @param null $source
     * @return mixed
     */
    protected function parseData($source = null)
    {
        try {
            $parser = new Yaml();
            return $parser->parse(file_get_contents($source));
        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }
    }

    protected function processData($data = null)
    {
        try {
            if (!isset ($data['websites'])) {
                throw new ComponentException(
                    sprintf(
                        "No websites found. Are you sure this component '%s' should be enabled?",
                        $this->getComponentAlias()
                    )
                );
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
                }
            }
        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }
    }

    protected function processWebsite($code, $websiteData)
    {
        try {

            // Load website via ObjectManager
            $this->log->logComment(sprintf("Checking if the website with code '%s' already exists", $code));
            $websiteFactory = new WebsiteFactory($this->objectManager, \Magento\Store\Model\Website::class);
            $website = $websiteFactory->create();
            $website->load($code, 'code');

            $canSave = false;

            // Check if it exists
            if ($website->getId()) {
                $this->log->logComment(sprintf("Website already exists with code '%s'", $code));
            } else {

                // If it does not exist, just set the existing data up with the website
                $canSave = true;
                $this->log->logComment(sprintf("Creating a new Website with code '%s'", $code));
                $website->setData($websiteData);
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
                    $this->log->logInfo(sprintf("Change '%s' from '%s' to '%s'", $key, $value, $websiteData[$key]));
                    $website->setData($key, $websiteData[$key]);
                    $canSave = true;
                } else {

                    // Skip setting the data
                    if ($website->getId()) {
                        $this->log->logComment(sprintf("No change for '%s' - '%s'", $key, $value));
                    } else {
                        $this->log->logInfo(sprintf("New setting for '%s' - '%s'", $key, $value));
                    }
                }
            }

            if ($canSave) {

                // Save the website
                $website->getResource()->save($website);
                $this->log->logInfo(sprintf("Saved website '%s'", $code));
            }
            return $website;
        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }
    }

    protected function processStoreGroup($storeGroupData, Website $website)
    {
        try {

            $this->log->logComment(
                sprintf("Checking if the store group with name '%s' already exists", $storeGroupData['name'])
            );

            // Attempt to load the Store Group via the object manager
            $groupFactory = new GroupFactory($this->objectManager, \Magento\Store\Model\Group::class);
            $storeGroup = $groupFactory->create();
            $storeGroup->load($storeGroupData['name'], 'name');

            $canSave = false;

            // Check if the store group already exists
            if ($storeGroup->getId()) {
                $this->log->logComment(sprintf("Store group already exists with name '%s'", $storeGroupData['name']));
            } else {

                // Create a new store group and set the basic data from source
                $this->log->logComment(sprintf("Creating a new website with name '%s'", $storeGroupData['name']));
                $storeGroup->setData($storeGroupData);
                $storeGroup->setWebsite($website);
                $canSave = true;
            }

            foreach ($storeGroup->getData() as $key => $value) {

                if (is_array($value)) {
                    continue;
                }

                // Set data if the data from source exists and is not the same as what is on magento's
                if (isset($storeGroupData[$key]) && $storeGroupData[$key] != $value) {
                    $this->log->logInfo(sprintf("Change '%s' from '%s' to '%s'", $key, $value, $storeGroupData[$key]));
                    $storeGroup->setData($key, $storeGroupData[$key]);
                    $canSave = true;
                } else {
                    if ($storeGroup->getId()) {
                        $this->log->logComment(sprintf("No change for '%s' - '%s'", $key, $value));
                    } else {
                        $this->log->logInfo(sprintf("New setting for '%s' - '%s'", $key, $value));
                    }
                }

            }

            if ($canSave) {

                // Save the store group
                $storeGroup->getResource()->save($storeGroup);
                $this->log->logInfo(sprintf("Saved store group '%s'", $storeGroup->getName()));
            }

            return $storeGroup;
        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }
    }

    protected function processStoreView($code, $storeViewData, Group $storeGroup)
    {
        try {

            // Load store view via ObjectManager
            $this->log->logComment(sprintf("Checking if the website with code '%s' already exists", $code));
            $storeFactory = new StoreFactory($this->objectManager, \Magento\Store\Model\Store::class);
            $storeView = $storeFactory->create();
            $storeView->load($code, 'code');

            $canSave = false;

            // Check if it exists
            if ($storeView->getId()) {
                $this->log->logComment(sprintf("Store view already exists with code '%s'", $code));
            } else {

                // If it does not exist, just set the existing data up with the store view
                $canSave = true;
                $this->log->logComment(sprintf("Creating a new Website with code '%s'", $code));
                $storeView->setData($storeViewData);
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
                    $this->log->logInfo(sprintf("Change '%s' from '%s' to '%s'", $key, $value, $storeViewData[$key]));
                    $storeView->setData($key, $storeViewData[$key]);
                    $canSave = true;
                } else {

                    // Skip setting the data
                    if ($storeView->getId()) {
                        $this->log->logComment(sprintf("No change for '%s' - '%s'", $key, $value));
                    } else {
                        $this->log->logInfo(sprintf("New setting for '%s' - '%s'", $key, $value));
                    }
                }
            }

            if ($canSave) {

                // Save the store view
                $storeView->getResource()->save($storeView);
                $this->log->logInfo(sprintf("Saved store view '%s'", $code));
            }
            return $storeView;
        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }
    }
}
