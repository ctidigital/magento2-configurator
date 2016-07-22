<?php
namespace CtiDigital\Configurator\Model;

class ComponentList implements ComponentListInterface
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
