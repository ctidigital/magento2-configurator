<?php

namespace CtiDigital\Configurator\Model;

use CtiDigital\Configurator\Component\ComponentAbstract;
use CtiDigital\Configurator\Model\Configurator\ConfigInterface;
use CtiDigital\Configurator\Exception\ComponentException;
use Magento\Framework\ObjectManagerInterface;
use Symfony\Component\Yaml\Parser;
use Magento\Framework\App\State;
use Magento\Framework\App\Area;

class Processor
{

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
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var State
     */
    protected $state;

    public function __construct(
        ConfigInterface $configInterface,
        ObjectManagerInterface $objectManager,
        LoggerInterface $logging,
        State $state
    ) {
        $this->log = $logging;
        $this->configInterface = $configInterface;
        $this->objectManager = $objectManager;
        $this->state = $state;
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

        /* @var ComponentAbstract $component */
        $component = $this->objectManager->create($componentClass);
        foreach ($componentConfig['sources'] as $source) {
            $component->setSource($source)->process();
        }

        // Check if there are environment specific nodes placed
        if (!isset($componentConfig['env'])) {

            // If not, continue to next component
            $this->log->logComment(
                sprintf("No environment node for '%s' component", $component->getComponentName())
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
                    $component->getComponentName()
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
                    $component->getComponentName()
                )
            );
            return;
        }
        
        // If there are sources for the environment, process them
        foreach ((array) $componentConfig['env'][$this->getEnvironment()]['sources'] as $source) {
            $component->setSource($source)->process();
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
        $component = $this->objectManager->create($componentClass);
        if ($component instanceof ComponentAbstract) {
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
}
