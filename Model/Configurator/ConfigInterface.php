<?php

namespace CtiDigital\Configurator\Model\Configurator;

interface ConfigInterface
{

    /**
     * Gets all the different available components
     * @return array
     */
    public function getAllComponents();

    /**
     * Gets a single component by its name
     *
     * @param String $name
     * @return mixed
     */
    public function getComponentByName($name);
}
