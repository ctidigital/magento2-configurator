<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Api\ComponentInterface;
use CtiDigital\Configurator\Api\ComponentListInterface;

class ComponentList implements ComponentListInterface
{
    public function __construct(
        private readonly array $components = []
    ) {}

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
