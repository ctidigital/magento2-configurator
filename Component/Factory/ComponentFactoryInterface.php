<?php
/**
 * @package  CtiDigital\Configurator
 * @author Bartosz Herba <b.herba@ctidigital.com>
 * @copyright 2017 CtiDigital
 */

namespace CtiDigital\Configurator\Component\Factory;

use CtiDigital\Configurator\Component\ComponentAbstract;

/**
 * Interface ComponentFactoryInterface
 */
interface ComponentFactoryInterface
{
    /**
     * @param string $componentClass
     * @param array $args
     *
     * @return ComponentAbstract
     */
    public function create($componentClass, array $args = []);
}
