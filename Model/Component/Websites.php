<?php

namespace CtiDigital\Configurator\Model\Component;

use CtiDigital\Configurator\Model\Exception\ComponentException;
use Symfony\Component\Yaml\Yaml;

class Websites extends ComponentAbstract
{

    protected $alias = 'websites';
    protected $name = 'Websites';

    /**
     * @param null $path
     * @return mixed
     */
    protected function parse($path = null)
    {
        try {
            return Yaml::parse(file_get_contents($path));
        } catch (ComponentException $e) {
            // @todo Handle Exception
        }
    }

    protected function process($data = null)
    {

    }
}