<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Tester\Exception\PendingException;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Bex\Behat\Magento2InitExtension\Fixtures\BaseFixture;

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
        // file prepared in features/bootstrap/Fixtures/master.yaml
        return true;
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

    protected function _getMagentoBaseDir()
    {
        $dir = $this->createMagentoObject('Magento\App\Dir');

        return $dir->getDir();
    }
}
