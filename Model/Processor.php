<?php

namespace CtiDigital\Configurator\Model;

use CtiDigital\Configurator\Model\Component\ComponentAbstract;
use CtiDigital\Configurator\Model\Configurator\ConfigInterface;
use CtiDigital\Configurator\Model\Exception\ComponentException;
use Magento\Framework\ObjectManagerInterface;
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
     * @var LoggingInterface
     */
    protected $log;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    public function __construct(
        ConfigInterface $configInterface,
        ObjectManagerInterface $objectManager,
        Logging $logging,
        \Magento\Framework\App\State $state
    ) {
        $this->log = $logging;
        $this->configInterface = $configInterface;
        $this->objectManager = $objectManager;
        $state->setAreaCode('adminhtml');
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
            if (!$this->isValidComponent($componentName)) {
                throw new ComponentException(
                    sprintf('%s component does not appear to be a valid component.', $componentName)
                );
            }

            $componentClass = $this->configInterface->getComponentByName($componentName);
            $this->components[$componentName] = new $componentClass($this->log);
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

                //print_r($master);

                // Validate master yaml
                $this->validateMasterYaml($master);

                // Loop through components and run them individually in the master.yaml order
                foreach ($master as $componentAlias => $componentConfig) {

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
                        continue;
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
                        continue;
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
                        continue;
                    }


                }


            } catch (ComponentException $e) {
                $this->log->logError($e->getMessage());
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
        $componentClass = $this->configInterface->getComponentByName($componentName);
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
