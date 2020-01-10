<?php

namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Api\ComponentInterface;
use CtiDigital\Configurator\Exception\ComponentException;
use CtiDigital\Configurator\Api\LoggerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\StoreFactory;
use Magento\Store\Model\WebsiteFactory;
use Magento\Theme\Model\ResourceModel\Theme\Collection;
use Magento\Theme\Model\ResourceModel\Theme\CollectionFactory;
use Magento\Config\Model\ResourceModel\Config as ConfigResource;
use Magento\Framework\App\Config as ScopeConfig;

class Config implements ComponentInterface
{
    const PATH_THEME_ID = 'design/theme/theme_id';

    protected $alias = 'config';
    protected $name = 'Configuration';
    protected $description = 'Component to set the store/system configuration values';

    /**
     * @var ConfigResource
     */
    protected $configResource;

    /**
     * @var ScopeConfig
     */
    protected $scopeConfig;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var WebsiteFactory
     */
    protected $websiteFactory;

    /**
     * @var StoreFactory
     */
    protected $storeFactory;

    /**
     * @var LoggerInterface
     */
    private $log;

    /**
     * Config constructor.
     * @param ConfigResource $configResource
     * @param ScopeConfig $scopeConfig
     * @param CollectionFactory $collectionFactory
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        ConfigResource $configResource,
        ScopeConfig $scopeConfig,
        CollectionFactory $collectionFactory,
        EncryptorInterface $encryptor,
        WebsiteFactory $websiteFactory,
        StoreFactory $storeFactory,
        LoggerInterface $log
    ) {
        $this->configResource = $configResource;
        $this->scopeConfig = $scopeConfig;
        $this->collectionFactory = $collectionFactory;
        $this->encryptor = $encryptor;
        $this->websiteFactory = $websiteFactory;
        $this->storeFactory = $storeFactory;
        $this->log = $log;
    }

    /**
     * @param $data
     * @SuppressWarnings(PHPMD)
     */
    public function execute($data = null)
    {
        try {
            $validScopes = array('global', 'websites', 'stores');
            foreach ($data as $scope => $configurations) {
                if (!in_array($scope, $validScopes)) {
                    throw new ComponentException(sprintf("This is not a valid scope '%s' in your config.", $scope));
                }

                if ($scope == "global") {
                    foreach ($configurations as $configuration) {
                        // Handle encryption parameter
                        $encryption = 0;
                        if (isset($configuration['encryption']) && $configuration['encryption'] == 1) {
                            $encryption = 1;
                        }

                        $convertedConfiguration = $this->convert($configuration);
                        $this->setGlobalConfig(
                            $convertedConfiguration['path'],
                            $convertedConfiguration['value'],
                            $encryption
                        );
                    }
                }

                if ($scope == "websites") {
                    foreach ($configurations as $code => $websiteConfigurations) {
                        foreach ($websiteConfigurations as $configuration) {
                            // Handle encryption parameter
                            $encryption = 0;
                            if (isset($configuration['encryption']) && $configuration['encryption'] == 1) {
                                $encryption = 1;
                            }
                            $convertedConfiguration = $this->convert($configuration);
                            $this->setWebsiteConfig(
                                $convertedConfiguration['path'],
                                $convertedConfiguration['value'],
                                $code,
                                $encryption
                            );
                        }
                    }
                }

                if ($scope == "stores") {
                    foreach ($configurations as $code => $storeConfigurations) {
                        foreach ($storeConfigurations as $configuration) {
                            // Handle encryption parameter
                            $encryption = 0;
                            if (isset($configuration['encryption']) && $configuration['encryption'] == 1) {
                                $encryption = 1;
                            }

                            $convertedConfiguration = $this->convert($configuration);
                            $this->setStoreConfig(
                                $convertedConfiguration['path'],
                                $convertedConfiguration['value'],
                                $code,
                                $encryption
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
            // Check existing value, skip if the same
            $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
            $existingValue = $this->scopeConfig->getValue($path, $scope);
            if ($value == $existingValue) {
                $this->log->logComment(sprintf("Global Config Already: %s = %s", $path, $value));
                return;
            }

            if ($encrypted) {
                $value = $this->encrypt($value);
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
            $logNest = 1;
            $scope = 'websites';

            // Prepare Website ID;
            $website = $this->websiteFactory->create();
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

            if ($encrypted) {
                $value = $this->encrypt($value);
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
            $logNest = 2;
            $scope = 'stores';

            $storeView = $this->storeFactory->create();
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

            if ($encrypted) {
                $value = $this->encrypt($value);
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

    private function encrypt($value)
    {
        return $this->encryptor->encrypt($value);
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
}
