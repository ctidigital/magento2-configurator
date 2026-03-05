<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Api;

use CtiDigital\Configurator\Model\Component\ComponentAbstract;

interface ConfigInterface
{
    /**
     * Gets all the different available components.
     */
    public function getAllComponents(): array;

    /**
     * Gets a single component by its name.
     */
    public function getComponentByName(string $name): ComponentAbstract;
}
