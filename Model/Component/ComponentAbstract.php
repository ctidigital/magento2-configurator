<?php

namespace CtiDigital\Configurator\Model\Component;

abstract class ComponentAbstract
{

    const ENABLED = 1;
    const DISABLED = 0;

    protected $alias;
    protected $name;

    /**
     * This is a human friendly component name for logging purposes.
     *
     * @return string
     */
    protected function getComponentName() {
        return $this->name;
    }

    /**
     * This is to provide a system friendly alias that can be used on the command line
     * so a component can be ran on its own as well as for logging purposes.
     *
     * @return string
     */
    protected function getComponentAlias() {
        return $this->alias;
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