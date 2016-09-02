<?php

namespace CtiDigital\Configurator\Model;

use CtiDigital\Configurator\Model\Component\ComponentAbstract;
use CtiDigital\Configurator\Model\Configurator\ConfigInterface;
use CtiDigital\Configurator\Model\Exception\ComponentException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Parser;

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
     * @var mixed
     */
    protected $log;

    public function __construct(ConfigInterface $configInterface, OutputInterface $output)
    {
        $this->log = new Logging($output);
        $this->configInterface = $configInterface;
    }

    /**
     * @param string $environment
     */
    public function setEnvironment($environment)
    {
        $this->environment = $environment;
    }

    /**
     * @param string $componentName
     */
    public function addComponent($componentName)
    {
        try {
            if (!$this->isValidComponent($componentname)) {
                throw new ComponentException(
                    sprintf('%s component does not appear to be a valid component.', $componentName)
                );
            }

            $this->components[$componentName] = $this->configInterface->getComponentByName($componentName);
        } catch (ComponentException $e) {
            throw $e;
        }
    }

    /**
     * @return string
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * @return array
     */
    public function getComponents()
    {
        return $this->components;
    }

    /**
     * Run the components individually
     */
    public function run()
    {
        // If the components list is empty, then the user would want to run all components in the master.yaml
        if (empty($this->components)) {

            try {

                // Read master yaml
                $masterPath = BP . '/app/etc/master.yaml';
                if (!file_exists($masterPath)) {
                    throw new ComponentException("Master YAML does not exist. Please create one in $masterPath");
                }
                $yamlContents = file_get_contents($masterPath);
                $yaml = new Parser();
                $master = $yaml->parse($yamlContents);
                print_r($master);

                // Validate master yaml
                $this->validateMasterYaml($master);

                // Loop through components and run them individually in the master.yaml order
                foreach ($master as $componentAlias => $componentConfiguration) {

                    $component = $this->configInterface->getComponentByName($componentAlias);
                    foreach ($componentConfiguration['sources'] as $i=>$source) {
                        $component->setSource($source)->process();
                    }

                    // Include any other attributes that comes through the master.yaml
                }


            } catch (ComponentException $e) {
                $this->log->logError($e->getMessage());
            }

        } else {

            // Loop through the specified components
            foreach ($this->components as $componentClass) {

                // Find component in the master.yaml and its associated settings
                $source = '';

                /* @var $component ComponentAbstract */
                $component = new $componentClass;
                $component->setSource($source)->process();

            }
        }
    }

    /**
     * See if the component in master yaml exists
     *
     * @param $componentName
     * @return bool
     */
    private function isValidComponent($componentName)
    {
        $component = $this->configInterface->getComponentByName($componentName);
        if ($component instanceof ComponentAbstract) {
            return true;
        }
        return false;
    }

    /**
     * Basic validation of master yaml requirements
     *
     * @param $master
     */
    private function validateMasterYaml($master)
    {
        try {
            foreach ($master as $componentAlias => $componentConfiguration) {

                // Check it has a enabled node
                if (!isset($componentConfiguration['enabled'])) {
                    throw new ComponentException(
                        sprintf('It appears %s does not have a "enabled" node. This is required.', $componentAlias)
                    );
                }

                // Check it has at least 1 data source
                $sourceCount = 0;
                if (isset($componentConfiguration['sources'])) {
                    foreach ($componentConfiguration['sources'] as $i => $source) {
                        $sourceCount++;
                    }
                }

                if (isset($componentConfiguration['env'])) {
                    foreach ($componentConfiguration['env'] as $env => $envData) {

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
