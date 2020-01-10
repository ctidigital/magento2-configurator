<?php

namespace CtiDigital\Configurator\Api;

interface ComponentListInterface
{
    /**
     * @param $componentAlias
     * @return ComponentInterface
     */
    public function getComponent($componentAlias);

    /**
     * @return ComponentInterface[]
     */
    public function getAllComponents();
}
