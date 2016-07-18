<?php

namespace CtiDigital\Configurator\Model\Component;

use CtiDigital\Configurator\Model\Exception\ComponentException;
use Symfony\Component\Yaml\Yaml;

class Websites extends ComponentAbstract
{

    protected $alias = 'websites';
    protected $name = 'Websites';

    /**
     * @todo 
     */
    protected function canParseAndProcess()
    {
        return $this::ENABLED;
    }

    /**
     * @param null $path
     * @return mixed
     */
    protected function parseData($path = null)
    {
        try {
            return Yaml::parse(file_get_contents($path));
        } catch (ComponentException $e) {
            // @todo Handle Exception
        }
    }

    protected function processData($data = null)
    {

    }
}