<?php

namespace CtiDigital\Configurator\Model\Component;

use CtiDigital\Configurator\Model\Exception\ComponentException;
use Symfony\Component\Yaml\Yaml;
use Magento\Framework\File\Csv;
use Magento\Framework\Filesystem\Driver\File;

class Products extends ComponentAbstract
{
    const TYPE_CSV = 'csv';
    const TYPE_YAML = 'yaml';

    protected $alias = 'products';
    protected $name = 'Products';
    protected $description = 'Component to import products using a CSV file.';
    protected $type;
    protected $importerFactory;

    public function __construct(
        \CtiDigital\Configurator\Model\LoggingInterface $log,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \FireGento\FastSimpleImport\Model\ImporterFactory $importerFactory
    ) {
        parent::__construct($log, $objectManager);
        $this->importerFactory = $importerFactory;
    }

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

    protected function parseData($source = null)
    {
        try {
            $fileType = $this->getFileType($source);
            if ($fileType === self::TYPE_CSV) {
                $this->type = self::TYPE_CSV;
                $file = new File();
                $parser = new Csv($file);
                return $parser->getData($source);
            } elseif ($fileType === self::TYPE_YAML) {
                $this->type = self::TYPE_YAML;
                $parser = new Yaml();
                return $parser->parse(file_get_contents($source));
            }
        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }
    }

    protected function processData($data = null)
    {
        if ($this->type === self::TYPE_CSV) {
            // Get the first row of the CSV file for the attribute columns.
            if (!isset($data[0])) {
                throw new ComponentException(
                    sprintf('The row data is not valid.')
                );
            }
            $attributeKeys = $this->getAttributesFromCsv($data);
            unset($data[0]);

            // Prepare the data
            $productsArray = array();

            foreach ($data as $product) {
                $productArray = array();
                foreach ($attributeKeys as $column => $code) {
                    $productArray[$code] = $product[$column];
                }
                $productsArray[] = $productArray;
            }

            try {
                $import = $this->importerFactory->create();
                $import->processImport($productsArray);
                $this->log->logInfo($import->getLogTrace());
            } catch (\Exception $e) {
                $this->log->logError($import->getErrorMessages());
                $this->log->logError($import->getLogTrace());
            }
        }
    }

    /**
     * Gets the file extension
     *
     * @param null $source
     * @return mixed
     */
    public function getFileType ($source = null)
    {
        // Get the file extension so we know how to load the file
        $sourceFileInfo = pathinfo($source);
        if (!isset($sourceFileInfo['extension'])) {
            throw new ComponentException(
                sprintf('Could not find a valid extension for the source file.')
            );
        }
        $fileType = $sourceFileInfo['extension'];
        return $fileType;
    }

    /**
     * Gets the first row of the CSV file as these should be the attribute keys
     *
     * @param null $data
     * @return array
     */
    public function getAttributesFromCsv ($data = null)
    {
        $attributes = array();
        foreach ($data[0] as $attributeCode) {
            $attributes[] = $attributeCode;
        }
        return $attributes;
    }
}