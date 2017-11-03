<?php

namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Exception\ComponentException;
use CtiDigital\Configurator\Model\LoggerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreFactory;
use Magento\Store\Model\WebsiteFactory;
use Magento\Theme\Model\ResourceModel\Theme\Collection;
use Magento\Theme\Model\ResourceModel\Theme\CollectionFactory;
use Symfony\Component\Yaml\Yaml;

class Config extends YamlComponentAbstract
{
    const PATH_THEME_ID = 'design/theme/theme_id';

    protected $alias = 'config';
    protected $name = 'Configuration';
    protected $description = 'Component to set the store/system configuration values';

    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $configResource;

    /**
     * @var \Magento\Framework\App\Config
     */
    protected $scopeConfig;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * Config constructor.
     *
     * @param LoggerInterface $log
     * @param ObjectManagerInterface $objectManager
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        LoggerInterface $log,
        ObjectManagerInterface $objectManager,
        CollectionFactory $collectionFactory
    ) {
        parent::__construct($log, $objectManager);

        $this->configResource = $this->objectManager->create(\Magento\Config\Model\ResourceModel\Config::class);
        $this->scopeConfig = $this->objectManager->create(\Magento\Framework\App\Config::class);
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @param $data
     * @SuppressWarnings(PHPMD)
     */
    protected function processData($data = null)
    {
        try {
            $validScopes = array('global', 'websites', 'stores');
            foreach ($data as $scope => $configurations) {

                if (!in_array($scope, $validScopes)) {
                    throw new ComponentException(sprintf("This is not a valid scope '%s' in your config.", $scope));
                }

                if ($scope == "global") {
                    foreach ($configurations as $configuration) {
                        $convertedConfiguration = $this->convert($configuration);
                        $this->setGlobalConfig(
                            $convertedConfiguration['path'],
                            $convertedConfiguration['value']
                        );
                    }
                }

                if ($scope == "websites") {
                    foreach ($configurations as $code => $websiteConfigurations) {
                        foreach ($websiteConfigurations as $configuration) {
                            $convertedConfiguration = $this->convert($configuration);
                            $this->setWebsiteConfig(
                                $convertedConfiguration['path'],
                                $convertedConfiguration['value'],
                                $code
                            );
                        }
                    }
                }

                if ($scope == "stores") {
                    foreach ($configurations as $code => $storeConfigurations) {
                        foreach ($storeConfigurations as $configuration) {
                            $convertedConfiguration = $this->convert($configuration);
                            $this->setStoreConfig(
                                $convertedConfiguration['path'],
                                $convertedConfiguration['value'],
                                $code
                            );
                        }
                    }
                }
            }
        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }
    }

    private function setGlobalConfig($path, $value, $encrypted = 0)
    {
        try {

            // Encrypted not supported at the moment
            if ($encrypted) {
                throw new ComponentException("There is no encryption support just yet");
            }

            // Check existing value, skip if the same
            $scope = \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
            $existingValue = $this->scopeConfig->getValue($path, $scope);
            if ($value == $existingValue) {
                $this->log->logComment(sprintf("Global Config Already: %s = %s", $path, $value));
                return;
            }

            // Save the config
            $this->configResource->saveConfig($path, $value, $scope, 0);
            $this->log->logInfo(sprintf("Global Config: %s = %s", $path, $value));

        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }
    }

    private function setWebsiteConfig($path, $value, $code, $encrypted = 0)
    {
        try {

            if ($encrypted) {
                throw new ComponentException("There is no encryption support just yet");
            }

            $logNest = 1;
            $scope = 'websites';

            // Prepare Website ID
            $websiteFactory = new WebsiteFactory($this->objectManager, \Magento\Store\Model\Website::class);
            $website = $websiteFactory->create();
            $website->load($code, 'code');
            if (!$website->getId()) {
                throw new ComponentException(sprintf("There is no website with the code '%s'", $code));
            }

            // Check existing value, skip if the same
            $existingValue = $this->scopeConfig->getValue($path, $scope, $code);
            if ($value == $existingValue) {
                $this->log->logComment(sprintf("Website '%s' Config Already: %s = %s", $code, $path, $value), $logNest);
                return;
            }

            // Save the config
            $this->configResource->saveConfig($path, $value, $scope, $website->getId());
            $this->log->logInfo(sprintf("Website '%s' Config: %s = %s", $code, $path, $value), $logNest);

        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }
    }

    /**
     * Convert paths or values before they're processed
     *
     * @param array $configuration
     *
     * @return array
     */
    protected function convert(array $configuration)
    {
        $convertedConfig = $configuration;
        if (isset($convertedConfig['path']) && isset($convertedConfig['value'])) {
            if ($this->isConfigTheme($convertedConfig['path'], $convertedConfig['value'])) {
                $convertedConfig['value'] = $this->getThemeIdByPath($convertedConfig['value']);
            }
        }
        return $convertedConfig;
    }

    private function setStoreConfig($path, $value, $code, $encrypted = 0)
    {
        try {

            if ($encrypted) {
                throw new ComponentException("There is no encryption support just yet");
            }

            $logNest = 2;
            $scope = 'stores';

            $storeFactory = new StoreFactory($this->objectManager, \Magento\Store\Model\Store::class);
            $storeView = $storeFactory->create();
            $storeView->load($code, 'code');
            if (!$storeView->getId()) {
                throw new ComponentException(sprintf("There is no store view with the code '%s'", $code));
            }

            // Check existing value, skip if the same
            $existingValue = $this->scopeConfig->getValue($path, $scope, $code);
            if ($value == $existingValue) {
                $this->log->logComment(sprintf("Store '%s' Config Already: %s = %s", $code, $path, $value), $logNest);
                return;
            }

            $this->configResource->saveConfig($path, $value, $scope, $storeView->getId());
            $this->log->logInfo(sprintf("Store '%s' Config: %s = %s", $code, $path, $value), $logNest);

        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }

    }

    /**
     * Checks if the config path is setting the theme by its path so we can get the ID
     *
     * @param $path
     * @param $value
     *
     * @return bool
     */
    public function isConfigTheme($path, $value)
    {
        if ($path === self::PATH_THEME_ID && is_int($value) === false) {
            return true;
        }
        return false;
    }

    /**
     * Get the theme ID by the path
     *
     * @param $themePath
     *
     * @return int
     */
    public function getThemeIdByPath($themePath)
    {
        /**
         * @var Collection $themeCollection
         */
        $themeCollection = $this->collectionFactory->create();
        $theme = $themeCollection->getThemeByFullPath($themePath);
        return $theme->getThemeId();
    }
}
