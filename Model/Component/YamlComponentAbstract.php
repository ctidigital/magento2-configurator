<?php

namespace CtiDigital\Configurator\Model\Component;

use Magento\Framework\Webapi\Exception;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use CtiDigital\Configurator\Model\Exception\ComponentException;

/**
 * Class YamlComponentAbstract
 *
 * Abstract Class for Components driven by YAML configuration
 * @package CtiDigital\Configurator\Model\Component
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
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

            $path = BP . '/' . $source;
            $data = file_get_contents($path);
            return (new Yaml())->parse($data);
        } catch (ParseException $e) {
            throw new ComponentException(
                sprintf('The %s component failed to parse. Error: %s.', $source, $e->getMessage())
            );
        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }
    }
}
