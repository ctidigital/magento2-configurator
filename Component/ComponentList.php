<?php

namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Api\ComponentListInterface;

class ComponentList implements ComponentListInterface
{
    /**
     * @var []
     */
    private $components;

    public function __construct(
        array $components = []
    ) {
        $this->components = $components;
    }

    /**
     * @inheritDoc
     */
    public function getComponent($componentAlias)
    {
        if (array_key_exists($componentAlias, $this->components) === true) {
            return $this->components[$componentAlias];
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getAllComponents()
    {
        return $this->components;
    }
}
