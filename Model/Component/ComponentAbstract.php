<?php

namespace CtiDigital\Configurator\Model\Component;

use CtiDigital\Configurator\Model\Exception\ComponentException;

abstract class ComponentAbstract
{

    const ENABLED = 1;
    const DISABLED = 0;

    protected $alias;
    protected $name;
    protected $source;
    protected $parsedData;

    /**
     * Obtain the source of the data.
     * Most likely to be a file path from the master.yaml
     *
     * @param $source
     */
    public function setSource($source)
    {
        $this->source = $source;
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
     * The function that runs the component (and every other component)
     */
    public function process()
    {

        try {

            // Check if a component can be parsed and processed
            if (!$this->canParseAndProcess()) {
                return; // @todo show some kind of logging
            }
            
//            $this->eventManager->dispatch('configurator_process_component_before',array('object'=>$this));
//            $this->eventManager->dispatch('configurator_process_component_before'.$this->alias,array('object'=>$this));

            $this->parsedData = $this->parseData($this->source);
            $this->processData($this->parsedData);

//            $this->eventManager->dispatch('configurator_process_component_after',array('object'=>$this));
//            $this->eventManager->dispatch('configurator_process_component_after'.$this->alias,array('object'=>$this));

        } catch (ComponentException $e) {
            //  @todo handle this gracefully
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
     * @param $data
     * @return mixed
     */
    abstract protected function parseData($data = null);

    /**
     * This method should be used to process the data and populate the Magento Database.
     *
     * @param $data
     * @return void
     */
    abstract protected function processData($data = null);
}