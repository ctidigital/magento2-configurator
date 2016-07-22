<?php
namespace CtiDigital\Configurator\Model;

interface ConfiguratorListInterface
{
    /**
     * Gets list of command instances
     *
     * @return \Symfony\Component\Console\Command\Command[]
     */
    public function getComponents();
}
