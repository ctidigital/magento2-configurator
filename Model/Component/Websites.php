<?php

namespace CtiDigital\Configurator\Model\Component;

use CtiDigital\Configurator\Model\Exception\ComponentException;
use Symfony\Component\Yaml\Yaml;

class Websites extends ComponentAbstract
{

    protected $alias = 'websites';
    protected $name = 'Websites';
    protected $description = 'Component to manage Websites, Stores and Store Views';

    /**
     * @return bool
     */
    protected function canParseAndProcess()
    {
        $path = BP . '/' . $this->source;
        if (!file_exists($path)) {
            throw new ComponentException(
                sprintf("Could not find file in path %s", $path)
            );
        }
        return true;
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
            $this->log->logError($e->getMessage());
        }
    }

    protected function processData($data = null)
    {
        $this->log->logInfo(print_r($data));
    }
}
