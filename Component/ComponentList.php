<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Api\ComponentInterface;
use CtiDigital\Configurator\Api\ComponentListInterface;

class ComponentList implements ComponentListInterface
{
    private array $components;

    public function __construct(
        array $components = []
    ) {
        $this->components = $components;
    }

    /**
     * @inheritDoc
     */
    public function getComponent(string $componentAlias): ComponentInterface|bool
    {
        if (array_key_exists($componentAlias, $this->components) === true) {
            return $this->components[$componentAlias];
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getAllComponents(): array
    {
        return $this->components;
    }
}
