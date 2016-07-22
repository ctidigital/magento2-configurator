<?php
namespace CtiDigital\Configurator\Model;

interface ComponentListInterface
{
    /**
     * Gets list of command instances
     *
     * @return \CtiDigital\Configurator\Model\Component\ComponentAbstract[]
     */
    public function getComponents();
}
