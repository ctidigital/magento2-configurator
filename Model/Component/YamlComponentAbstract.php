<?php

namespace CtiDigital\Configurator\Model\Component;

use CtiDigital\Configurator\Model\Exception\ComponentException;
use Symfony\Component\Yaml\Yaml;

/**
 * Class YamlComponentAbstract
 *
 * Abstract Class for Components driven by YAML configuration
 * @package CtiDigital\Configurator\Model\Component
 */
abstract class YamlComponentAbstract extends ComponentAbstract
{

    /**
     * Check YAML file exists
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
     * Convert YAML to array
     *
     * @param null $source
     * @return mixed
     */
    protected function parseData($source = null)
    {
        try {
            if ($source == null) {
                throw new ComponentException(
                    sprintf('The %s component requires to have a file source definition.', $this->alias)
                );
            }

            $parser = new Yaml();
            return $parser->parse(file_get_contents($source));
        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }
    }
}
