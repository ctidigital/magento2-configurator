<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Api;

interface ComponentListInterface
{
    /**
     * Get a component by alias, or false if not found.
     *
     * @param string $componentAlias
     * @return ComponentInterface|bool
     */
    public function getComponent(string $componentAlias): ComponentInterface|bool;

    /**
     * Return all registered components.
     *
     * @return ComponentInterface[]
     */
    public function getAllComponents(): array;
}
