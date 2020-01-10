<?php

namespace CtiDigital\Configurator\Model;

use CtiDigital\Configurator\Api\ComponentInterface;
use CtiDigital\Configurator\Api\FileComponentInterface;
use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Component\ComponentAbstract;
use CtiDigital\Configurator\Exception\ComponentException;
use CtiDigital\Configurator\Api\ConfigInterface;
use CtiDigital\Configurator\Component\Factory\ComponentFactoryInterface;
use Symfony\Component\Yaml\Parser;
use Magento\Framework\App\State;
use Magento\Framework\App\Area;
use Symfony\Component\Yaml\Yaml;

class Processor
{
    const SOURCE_YAML = 'yaml';
    const SOURCE_CSV = 'csv';
    const SOURCE_JSON = 'json';

    /**
     * @var string
     */
    protected $environment;

    /**
     * @var array
     */
    protected $components = array();

    /**
     * @var ConfigInterface
     */
    protected $configInterface;

    /**
     * @var LoggerInterface
     */
    protected $log;

    /**
     * @var State
     */
    protected $state;

    /**
     * @var ComponentFactoryInterface
     */
    protected $componentFactory;

    /**
     * Processor constructor.
     *
     * @param ConfigInterface $configInterface
     * @param ComponentFactoryInterface $componentFactory
     * @param LoggerInterface $logging
     * @param State $state
     */
    public function __construct(
        ConfigInterface $configInterface,
        LoggerInterface $logging,
        State $state,
        ComponentFactoryInterface $componentFactory
    ) {
        $this->log = $logging;
        $this->configInterface = $configInterface;
        $this->state = $state;
        $this->componentFactory = $componentFactory;
    }

    public function getLogger()
    {
        return $this->log;
    }

    /**
     * @param string $componentName
     * @return Processor
     */
    public function addComponent($componentName)
    {
        $this->components[$componentName] = $componentName;
        return $this;
    }

    /**
     * @return array
     */
    public function getComponents()
    {
        return $this->components;
    }

    /**
     * @param string $environment
     * @return Processor
     */
    public function setEnvironment($environment)
    {
        $this->environment = $environment;
        return $this;
    }

    /**
     * @return string
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * Run the components individually
     */
    public function run()
    {
        // If the components list is empty, then the user would want to run all components in the master.yaml
        if (empty($this->components)) {
            $this->runAllComponents();
            return;
        }

        $this->runIndividualComponents();
    }

    private function runIndividualComponents()
    {
        try {
            // Get the master yaml
            $master = $this->getMasterYaml();

            // Loop through the components
            foreach ($this->components as $componentAlias) {
                // Get the config for the component from the master yaml array
                if (!isset($master[$componentAlias])) {
                    throw new ComponentException(
                        sprintf("No master yaml definition with the alias '%s' found", $componentAlias)
                    );
                }

                $masterConfig = $master[$componentAlias];

                // Run that component
                $this->state->emulateAreaCode(
                    Area::AREA_ADMINHTML,
                    [$this, 'runComponent'],
                    [$componentAlias, $masterConfig]
                );
            }
        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }
    }

    private function runAllComponents()
    {
        try {
            // Get the master yaml
            $master = $this->getMasterYaml();

            // Loop through components and run them individually in the master.yaml order
            foreach ($master as $componentAlias => $componentConfig) {
                // Run the component in question
                $this->state->emulateAreaCode(
                    Area::AREA_ADMINHTML,
                    [$this, 'runComponent'],
                    [$componentAlias, $componentConfig]
                );
            }
        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }
    }

    public function runComponent($componentAlias, $componentConfig)
    {
        $this->log->logComment("");
        $this->log->logComment(str_pad("----------------------", (22 + strlen($componentAlias)), "-"));
        $this->log->logComment(sprintf("| Loading component %s |", $componentAlias));
        $this->log->logComment(str_pad("----------------------", (22 + strlen($componentAlias)), "-"));

        $componentClass = $this->configInterface->getComponentByName($componentAlias);

        /* @var ComponentInterface $component */
        $component = $this->componentFactory->create($componentClass);

        $sourceType = (isset($componentConfig['type']) === true) ? $componentConfig['type'] : null;

        if (isset($componentConfig['sources'])) {
            foreach ($componentConfig['sources'] as $source) {
                $sourceData = ($component instanceof FileComponentInterface) ?
                    $source :
                    $this->parseData($source, $sourceType);
                $component->execute($sourceData);
            }
        }

        // Check if there are environment specific nodes placed
        if (!isset($componentConfig['env'])) {
            // If not, continue to next component
            $this->log->logComment(
                sprintf("No environment node for '%s' component", $componentAlias)
            );
            return;
        }

        // Check if there is a node for this particular environment
        if (!isset($componentConfig['env'][$this->getEnvironment()])) {
            // If not, continue to next component
            $this->log->logComment(
                sprintf(
                    "No '%s' environment specific node for '%s' component",
                    $this->getEnvironment(),
                    $componentAlias
                )
            );
            return;
        }

        // Check if there are sources for the environment
        if (!isset($componentConfig['env'][$this->getEnvironment()]['sources'])) {
            // If not continue
            $this->log->logComment(
                sprintf(
                    "No '%s' environment specific sources for '%s' component",
                    $this->getEnvironment(),
                    $componentAlias
                )
            );
            return;
        }

        // If there are sources for the environment, process them
        foreach ((array) $componentConfig['env'][$this->getEnvironment()]['sources'] as $source) {
            $sourceType = (isset($componentConfig['type']) === true) ? $componentConfig['type'] : null;
            $sourceData = $this->parseData($source, $sourceType);
            $component->execute($sourceData);
        }
    }

    /**
     * @return array
     */
    private function getMasterYaml()
    {
        // Read master yaml
        $masterPath = BP . '/app/etc/master.yaml';
        if (!file_exists($masterPath)) {
            throw new ComponentException("Master YAML does not exist. Please create one in $masterPath");
        }
        $this->log->logComment(sprintf("Found Master YAML"));
        $yamlContents = file_get_contents($masterPath);
        $yaml = new Parser();
        $master = $yaml->parse($yamlContents);

        // Validate master yaml
        $this->validateMasterYaml($master);

        return $master;
    }

    /**
     * See if the component in master yaml exists
     *
     * @param $componentName
     * @return bool
     */
    private function isValidComponent($componentName)
    {
        if ($this->log->getLogLevel() > \Symfony\Component\Console\Output\OutputInterface::VERBOSITY_NORMAL) {
            $this->log->logQuestion(sprintf("Does the %s component exist?", $componentName));
        }
        $componentClass = $this->configInterface->getComponentByName($componentName);

        if (!$componentClass) {
            $this->log->logError(sprintf("The %s component has no class name.", $componentName));
            return false;
        }

        $this->log->logComment(sprintf("The %s component has %s class name.", $componentName, $componentClass));
        $component = $this->componentFactory->create($componentClass);
        if ($component instanceof ComponentInterface) {
            return true;
        }
        return false;
    }

    /**
     * Basic validation of master yaml requirements
     *
     * @param $master
     * @SuppressWarnings(PHPMD)
     */
    private function validateMasterYaml($master)
    {
        try {
            foreach ($master as $componentAlias => $componentConfig) {
                // Check it has a enabled node
                if (!isset($componentConfig['enabled'])) {
                    throw new ComponentException(
                        sprintf('It appears %s does not have a "enabled" node. This is required.', $componentAlias)
                    );
                }

                // Check it has at least 1 data source
                $sourceCount = 0;
                if (isset($componentConfig['sources'])) {
                    foreach ($componentConfig['sources'] as $i => $source) {
                        $sourceCount++;
                    }
                }

                if (isset($componentConfig['env'])) {
                    foreach ($componentConfig['env'] as $envData) {
                        if (isset($envData['sources'])) {
                            foreach ($envData['sources'] as $i => $source) {
                                $sourceCount++;
                            }
                        }
                    }
                }

                if ($sourceCount < 1) {
                    throw new ComponentException(
                        sprintf('It appears there are no data sources for the %s component.', $componentAlias)
                    );
                }

                // Check the component exist
                if (!$this->isValidComponent($componentAlias)) {
                    throw new ComponentException(
                        sprintf(
                            '%s not a valid component. Please verify using bin/magento component:list.',
                            $componentAlias
                        )
                    );
                }
            }
        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }
    }

    private function parseData($source, $sourceType)
    {
        if ($this->canParseAndProcess($source) === true) {
            $ext = ($sourceType !== null) ? $sourceType : $this->getExtension($source);
            $sourceData = $this->getData($source);
            if ($ext === self::SOURCE_YAML) {
                return $this->parseYamlData($sourceData);
            }
            if ($ext === self::SOURCE_CSV) {
                return $this->parseCsvData($sourceData);
            }
            if ($ext === self::SOURCE_JSON) {
                return $this->parseJsonData($sourceData);
            }
        }
    }

    /**
     * This method is used to check whether the data from file or a third party
     * can be parsed and processed. (e.g. does a YAML file exist for it?)
     *
     * This will determine whether the component is enabled or disabled.
     *
     * @return bool
     */
    private function canParseAndProcess($source)
    {
        $path = BP . '/' . $source;
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        if ($this->isSourceRemote($source) === false && !file_exists($path)) {
            throw new ComponentException(
                sprintf("Could not find file in path %s", $path)
            );
        }
        return true;
    }

    /**
     * @return true
     */
    public function isSourceRemote($source)
    {
        return (filter_var($source, FILTER_VALIDATE_URL) !== false) ? true : false;
    }

    /**
     * @param $source
     * @return string
     * @throws \Exception
     */
    private function getExtension($source)
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $extension = pathinfo($source, PATHINFO_EXTENSION);
        if (strtolower($extension) === 'yaml') {
            return self::SOURCE_YAML;
        }
        if (strtolower($extension) === 'csv') {
            return self::SOURCE_CSV;
        }
        if (strtolower($extension) === 'json') {
            return self::SOURCE_JSON;
        }
        throw new ComponentException(sprintf('Source "%s" does not have a valid file extension.', $source));
    }

    /**
     * @param $source
     * @return array|bool|false|float|int|mixed|string|null
     * @throws \Exception
     */
    private function getData($source)
    {
        return ($this->isSourceRemote($source) === true) ?
            $this->getRemoteData($source) :
            file_get_contents(BP . '/' . $source);
    }

    /**
     * @param $source
     * @return array|bool|float|int|mixed|string|null
     * @throws \Exception
     */
    public function getRemoteData($source)
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $streamContext = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $remoteFile = file_get_contents($source, false, $streamContext);
        return $remoteFile;
    }

    /**
     * @param $source
     * @return mixed
     */
    private function parseYamlData($source)
    {
        return (new Yaml())->parse($source);
    }

    /**
     * @param $source
     * @return array
     * @throws \Exception
     */
    private function parseCsvData($source)
    {
        $lines = explode("\n", $source);
        $headerRow = str_getcsv(array_shift($lines));
        $csvData = [$headerRow];
        foreach ($lines as $line) {
            $csvLine = str_getcsv($line);
            $csvRow = [];
            foreach ($headerRow as $key => $column) {
                $csvRow[$key] = (array_key_exists($key, $csvLine) === true) ? $csvLine[$key] : '';
            }
            $csvData[] = $csvRow;
        }
        return $csvData;
    }

    /**
     * @param $source
     * @return array|bool|float|int|mixed|string|null
     */
    private function parseJsonData($source)
    {
        return $jsonData = json_decode($source);
    }
}
