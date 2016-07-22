<?php
namespace CtiDigital\Configurator\Console;

use CtiDigital\Configurator\Model\ConfiguratorListInterface;

class ComponentList implements ConfiguratorListInterface
{
    /**
     * @var string[]
     */
    protected $components;

    /**
     * Constructor
     *
     * @param array $components
     */
    public function __construct(array $components = [])
    {
        $this->components = $components;
    }

    /**
     * {@inheritdoc}
     */
    public function getComponents()
    {
        return $this->components;
    }
}
