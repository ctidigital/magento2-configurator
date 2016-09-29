<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Tester\Exception\PendingException;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Bex\Behat\Magento2InitExtension\Fixtures\BaseFixture;
use Magento\Store\Model\ScopeInterface as Scope;
use Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfig;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends BaseFixture implements Context
{
    /** @var string Path to PHP executable */
    private $phpBin;

    public function __construct()
    {
        parent::__construct();

        $finder = new PhpExecutableFinder();
        if (($this->phpBin = $finder->find()) === false) {
            throw new \RuntimeException('Unable to find PHP executable.');
        }
    }

    /**
     * @Given I have a yaml file which describes some websites and stores
     */
    public function iHaveAYamlFileWhichDescribesSomeWebsitesAndStores()
    {
        $this->ensureFilesExist(
            [
                'features/bootstrap/Fixtures/master.yaml',
                'features/bootstrap/Fixtures/websites.yaml',
            ]
        );
    }

    /**
     * @When I run the configurator's cli tool with :component component for :environment environment
     */
    public function iRunTheConfiguratorSCliTool($component, $environment)
    {
        $baseDir = getcwd() . '/../../../';
        $command = sprintf(
            '%s bin/magento configurator:run -vvv --env=%s --component=%s -f features/bootstrap/Fixtures/master.yaml',
            $this->phpBin,
            escapeshellarg($environment),
            escapeshellarg($component)
        );

        $shellEnvironment = array_merge($_ENV, ['XDEBUG_CONFIG' => '']);
        $importerProcess = new Process($command, $baseDir, $shellEnvironment);
        $importerProcess->run();

        if (!$importerProcess->isSuccessful()) {
            throw new \RuntimeException(
                $command . ' failed: ' . $importerProcess->getOutput() . $importerProcess->getErrorOutput()
            );
        }
    }

    /**
     * @Then Magento database should have the desired websites and stores
     */
    public function iMagentoDatabaseShouldHaveTheDesiredWebsitesAndStores()
    {
        /** @var Magento\Store\Model\StoreManagerInterface $storeManager */
        $storeManager = $this->createMagentoObject('Magento\Store\Model\StoreManager');

        $expectedWebsites = [
            'hu' => 'HU website',
            'uk' => 'UK website',
            'ch' => 'CH website',
        ];
        foreach ($expectedWebsites as $code => $name) {
            $website = $storeManager->getWebsite($code);
            if ($website->getName() !== $name) {
                throw new \Exception("Website '$name' not found");
            }
        }

        $expectedStoreViews = [
            'hu_hu' => 'HU Store View',
            'en_uk' => 'UK Store View',
            'de_ch' => 'CH Store View German Language',
            'fr_ch' => 'CH Store View French Language',
            'it_ch' => 'CH Store View Italian Language',
        ];
        foreach ($expectedStoreViews as $code => $name) {
            $store = $storeManager->getStore($code);
            if ($store->getName() !== $name) {
                throw new \Exception("Store '$name' not found");
            }
        }
    }

    /**
     * @Given I have yaml files which describes store configuration for :name environment
     * @Given I have yaml files which describes store configuration for all environments
     */
    public function iHaveYamlFilesWhichDescribesStoreConfigurationForLocalEnvironment($name = false)
    {
        $environment = $name ? "$name/" : '';

        $this->ensureFilesExist(
            [
                'features/bootstrap/Fixtures/master.yaml',
                "features/bootstrap/Fixtures/{$environment}global.yaml",
                "features/bootstrap/Fixtures/{$environment}base-website-config.yaml",
            ]
        );
    }

    /**
     * @Then Magento database should have the desired configuration applied for local environment
     */
    public function magentoDatabaseShouldHaveTheDesiredConfigurationAppliedForLocalEnvironment()
    {
        $expectedConfiguration = [
            'general/country/default' => [
                ['scope_type' => ScopeConfig::SCOPE_TYPE_DEFAULT, 'scope_code' => '', 'value' => 'HU'],
                ['scope_type' => Scope::SCOPE_WEBSITE, 'scope_code' => 'hu', 'value' => 'HU'],
                ['scope_type' => Scope::SCOPE_WEBSITE, 'scope_code' => 'uk', 'value' => 'HU'],
                ['scope_type' => Scope::SCOPE_WEBSITE, 'scope_code' => 'ch', 'value' => 'HU'],
                ['scope_type' => Scope::SCOPE_STORE, 'scope_code' => 'de_ch', 'value' => 'HU'],
                ['scope_type' => Scope::SCOPE_STORE, 'scope_code' => 'fr_ch', 'value' => 'HU'],
                ['scope_type' => Scope::SCOPE_STORE, 'scope_code' => 'it_ch', 'value' => 'HU'],
            ],
            'general/locale/code' => [
                ['scope_type' => ScopeConfig::SCOPE_TYPE_DEFAULT, 'scope_code' => '', 'value' => 'de_FR'],
                ['scope_type' => Scope::SCOPE_WEBSITE, 'scope_code' => 'hu', 'value' => 'de_FR'],
                ['scope_type' => Scope::SCOPE_WEBSITE, 'scope_code' => 'uk', 'value' => 'de_FR'],
                ['scope_type' => Scope::SCOPE_WEBSITE, 'scope_code' => 'ch', 'value' => 'de_FR'],
                ['scope_type' => Scope::SCOPE_STORE, 'scope_code' => 'de_ch', 'value' => 'de_CH'],
                ['scope_type' => Scope::SCOPE_STORE, 'scope_code' => 'fr_ch', 'value' => 'fr_CH'],
                ['scope_type' => Scope::SCOPE_STORE, 'scope_code' => 'it_ch', 'value' => 'it_CH'],
            ],
            'general/store_information/name' => [
                ['scope_type' => ScopeConfig::SCOPE_TYPE_DEFAULT, 'scope_code' => '', 'value' => 'Defaut store'],
                ['scope_type' => Scope::SCOPE_WEBSITE, 'scope_code' => 'hu', 'value' => 'Hungarian webshop'],
                ['scope_type' => Scope::SCOPE_WEBSITE, 'scope_code' => 'uk', 'value' => 'English store'],
                ['scope_type' => Scope::SCOPE_WEBSITE, 'scope_code' => 'ch', 'value' => 'Defaut store'],
                ['scope_type' => Scope::SCOPE_STORE, 'scope_code' => 'de_ch', 'value' => 'Swiss store in German'],
                ['scope_type' => Scope::SCOPE_STORE, 'scope_code' => 'fr_ch', 'value' => 'Swiss-French store'],
                ['scope_type' => Scope::SCOPE_STORE, 'scope_code' => 'it_ch', 'value' => 'Swiss store in Italian'],
            ],
        ];

        $this->ensureConfigurationIsSet($expectedConfiguration);
    }

    /**
     * @Then Magento database should have the desired configuration applied for production environment
     */
    public function magentoDatabaseShouldHaveTheDesiredConfigurationAppliedForProductionEnvironment()
    {
        $expectedConfiguration = [
            'general/country/default' => [
                ['scope_type' => ScopeConfig::SCOPE_TYPE_DEFAULT, 'scope_code' => '', 'value' => 'HU'],
                ['scope_type' => Scope::SCOPE_WEBSITE, 'scope_code' => 'hu', 'value' => 'HU'],
                ['scope_type' => Scope::SCOPE_WEBSITE, 'scope_code' => 'uk', 'value' => 'HU'],
                ['scope_type' => Scope::SCOPE_WEBSITE, 'scope_code' => 'ch', 'value' => 'HU'],
                ['scope_type' => Scope::SCOPE_STORE, 'scope_code' => 'de_ch', 'value' => 'HU'],
                ['scope_type' => Scope::SCOPE_STORE, 'scope_code' => 'fr_ch', 'value' => 'HU'],
                ['scope_type' => Scope::SCOPE_STORE, 'scope_code' => 'it_ch', 'value' => 'HU'],
            ],
            'general/locale/code' => [
                ['scope_type' => ScopeConfig::SCOPE_TYPE_DEFAULT, 'scope_code' => '', 'value' => 'de_CH'],
                ['scope_type' => Scope::SCOPE_WEBSITE, 'scope_code' => 'hu', 'value' => 'de_CH'],
                ['scope_type' => Scope::SCOPE_WEBSITE, 'scope_code' => 'uk', 'value' => 'de_CH'],
                ['scope_type' => Scope::SCOPE_WEBSITE, 'scope_code' => 'ch', 'value' => 'de_CH'],
                ['scope_type' => Scope::SCOPE_STORE, 'scope_code' => 'de_ch', 'value' => 'de_CH'],
                ['scope_type' => Scope::SCOPE_STORE, 'scope_code' => 'fr_ch', 'value' => 'fr_CH'],
                ['scope_type' => Scope::SCOPE_STORE, 'scope_code' => 'it_ch', 'value' => 'it_CH'],
            ],
            'general/store_information/name' => [
                ['scope_type' => ScopeConfig::SCOPE_TYPE_DEFAULT, 'scope_code' => '', 'value' => 'Defaut store'],
                ['scope_type' => Scope::SCOPE_WEBSITE, 'scope_code' => 'hu', 'value' => 'Hungarian store'],
                ['scope_type' => Scope::SCOPE_WEBSITE, 'scope_code' => 'uk', 'value' => 'English webshop'],
                ['scope_type' => Scope::SCOPE_WEBSITE, 'scope_code' => 'ch', 'value' => 'Defaut store'],
                ['scope_type' => Scope::SCOPE_STORE, 'scope_code' => 'de_ch', 'value' => 'Swiss-German store'],
                ['scope_type' => Scope::SCOPE_STORE, 'scope_code' => 'fr_ch', 'value' => 'Swiss store in Franch'],
                ['scope_type' => Scope::SCOPE_STORE, 'scope_code' => 'it_ch', 'value' => 'Swiss store in Italian'],
            ],
        ];

        $this->ensureConfigurationIsSet($expectedConfiguration);
    }

    protected function _getMagentoBaseDir()
    {
        $dir = $this->createMagentoObject('Magento\App\Dir');

        return $dir->getDir();
    }

    /**
     * @param array $fileNames
     *
     * @throws RuntimeException Thrown when any of the given files does not exist.
     */
    protected function ensureFilesExist(array $fileNames)
    {
        foreach ($fileNames as $filename) {
            if (!file_exists($filename)) {
                throw new RuntimeException('Configuration file does not exist: ' . $filename);
            }
        }
    }

    /**
     * @param $expectedConfiguration
     *
     * @throws DomainException Thrown when the configuration value differs from the expectation.
     */
    private function ensureConfigurationIsSet($expectedConfiguration)
    {
        /** @var \Magento\Framework\App\Config\ScopePool $scopePool */
        $scopePool = $this->getObjectManager()->get('Magento\Framework\App\Config\ScopePool');
        $scopePool->clean();

        /** @var \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig */
        $scopeConfig = $this->createMagentoObject('Magento\Framework\App\Config\ScopeConfigInterface');

        foreach ($expectedConfiguration as $path => $configs) {
            foreach ($configs as $config) {
                $value = $scopeConfig->getValue($path, $config['scope_type'], $config['scope_code']);
                if ($value !== $config['value']) {
                    throw new DomainException(
                        sprintf(
                            'Configuration value for "%s" under scope %s(%s) expected to be "%s", but got "%s".',
                            $path,
                            $config['scope_type'],
                            $config['scope_code'],
                            $config['value'],
                            $value
                        )
                    );
                }
            }
        }
    }
}
