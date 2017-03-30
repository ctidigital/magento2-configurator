<?php

namespace CtiDigital\Configurator\Model\Component;

use Magento\Framework\File\Csv;
use Magento\Framework\Filesystem\Driver\File;
use CtiDigital\Configurator\Model\Exception\ComponentException;

/**
 * Class CsvComponentAbstract
 *
 * Abstract Class for Components driven by CSV configuration
 * @package CtiDigital\Configurator\Model\Component
 */
abstract class CsvComponentAbstract extends ComponentAbstract
{

    /**
     * Check CSV file exists
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
     * Convert CSV to array
     * @param null $source
     * @return array
     */
    protected function parseData($source = null)
    {
        try {
            if ($source == null) {
                throw new ComponentException(
                    sprintf('The %s component requires to have a file source definition.', $this->alias)
                );
            }

            $file = new File();
            $parser = new Csv($file);

            return $parser->getData($source);

        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }

        return null;
    }
}
