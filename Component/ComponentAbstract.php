<?php

namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Exception\ComponentException;
use CtiDigital\Configurator\Api\LoggerInterface;
use Magento\Framework\ObjectManagerInterface;

abstract class ComponentAbstract
{

    const ENABLED = 1;
    const DISABLED = 0;

    protected $log;
    protected $alias;
    protected $name;
    protected $source;
    protected $parsedData;
    protected $objectManager;
    protected $description = 'Unknown Component';

    public function __construct(
        LoggerInterface $log,
        ObjectManagerInterface $objectManager
    ) {
        $this->log = $log;
        $this->objectManager = $objectManager;
    }

    /**
     * Obtain the source of the data.
     * Most likely to be a file path from the master.yaml
     *
     * @param $source
     * @return ComponentAbstract
     */
    public function setSource($source)
    {
        $this->source = $source;
        return $this;
    }

    /**
     * This is a human friendly component name for logging purposes.
     *
     * @return string
     */
    public function getComponentName()
    {
        return $this->name;
    }

    /**
     * This is to provide a system friendly alias that can be used on the command line
     * so a component can be ran on its own as well as for logging purposes.
     *
     * @return string
     */
    public function getComponentAlias()
    {
        return $this->alias;
    }

    /**
     * Gets a small description of the component used for when listing the component
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * The function that runs the component (and every other component)
     */
    public function process()
    {

        try {

            // Check if a component can be parsed and processed
            if (!$this->canParseAndProcess()) {
                return; // @todo show some kind of logging
            }

            // @todo Include some events to dispatch.
//            $this->eventManager->dispatch('configurator_parse_component_before',array('object'=>$this));
//            $this->eventManager->dispatch('configurator_parse_component_before'.$this->alias,array('object'=>$this));

            $this->log->logComment(sprintf("Starting to parse data for %s", $this->getComponentName()));
            $this->parsedData = $this->parseData($this->source);
            $this->log->logComment(sprintf("Finished parsing data for %s", $this->getComponentName()));

//            $this->eventManager->dispatch(
//                'configurator_process_component_before',
//                array('object'=>$this,'source'=>$this->source)
//            );
//            $this->eventManager->dispatch('configurator_process_component_before'.$this->alias,
//                array('object'=>$this,'source'=>$this->source)
//            );

            $this->log->logComment(sprintf("Starting to process data for %s", $this->getComponentName()));
            $this->processData($this->parsedData);
            $this->log->logComment(sprintf("Finished processing data for %s", $this->getComponentName()));

//            $this->eventManager->dispatch('configurator_process_component_after',array('object'=>$this));
//            $this->eventManager->dispatch('configurator_process_component_after'.$this->alias,array('object'=>$this));

        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
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
    abstract protected function canParseAndProcess();

    /**
     * Whether it be from many files or an external database, parsing (pre-processing)
     * the data is done here.
     *
     * @param $source
     * @return mixed
     */
    abstract protected function parseData($source = null);

    /**
     * This method should be used to process the data and populate the Magento Database.
     *
     * @param $data
     * @return void
     */
    abstract protected function processData($data = null);
}
