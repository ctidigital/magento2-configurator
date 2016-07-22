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
     * @param null $source
     * @return mixed
     */
    protected function parseData($source = null)
    {
        try {
            $parser = new Yaml();
            return $parser->parse(file_get_contents($source));
        } catch (ComponentException $e) {
            // @todo Handle Exception
        }
    }

    protected function processData($data = null)
    {

    }
}