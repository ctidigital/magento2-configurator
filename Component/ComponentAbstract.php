<?php

namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Exception\ComponentException;
use CtiDigital\Configurator\Api\LoggerInterface;
use Magento\Framework\File\Csv;
use Magento\Framework\Filesystem\Driver\File;
use Symfony\Component\Yaml\Yaml;

abstract class ComponentAbstract
{
    const ENABLED = 1;
    const DISABLED = 0;

    protected $log;
    protected $alias;
    protected $name;
    protected $source;
    protected $parsedData;
    protected $objectManager;
    protected $description = 'Unknown Component';

    /**
     * @var string
     */
    private $sourceFileType;

    public function __construct(
        LoggerInterface $log
    ) {
        $this->log = $log;
    }

    /**
     * Obtain the source of the data.
     * Most likely to be a file path from the master.yaml
     *
     * @param $source
     * @return ComponentAbstract
     */
    public function setSource($source)
    {
        $this->source = $source;
        return $this;
    }

    /**
     * This is a human friendly component name for logging purposes.
     *
     * @return string
     */
    public function getComponentName()
    {
        return $this->name;
    }

    /**
     * This is to provide a system friendly alias that can be used on the command line
     * so a component can be ran on its own as well as for logging purposes.
     *
     * @return string
     */
    public function getComponentAlias()
    {
        return $this->alias;
    }

    /**
     * Gets a small description of the component used for when listing the component
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * The function that runs the component (and every other component)
     */
    public function process()
    {
        try {
            // Check if a component can be parsed and processed
            if (!$this->canParseAndProcess()) {
                return; // @todo show some kind of logging
            }
            $this->log->logComment(sprintf("Starting to parse data for %s", $this->getComponentName()));
            $this->parsedData = $this->parseData($this->source);
            $this->log->logComment(sprintf("Finished parsing data for %s", $this->getComponentName()));

            $this->log->logComment(sprintf("Starting to process data for %s", $this->getComponentName()));
            $this->processData($this->parsedData);
            $this->log->logComment(sprintf("Finished processing data for %s", $this->getComponentName()));
        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }
    }

    /**
     * @param $type
     */
    public function setSourceFileType($type)
    {
        if ($type !== self::SOURCE_JSON && $type !== self::SOURCE_CSV && $type !== self::SOURCE_YAML) {
            throw new ComponentException(sprintf('The source file type %s is not valid.', $type));
        }
        $this->sourceFileType = $type;
    }

    /**
     * @return string
     */
    public function getSourceFileType()
    {
        return $this->sourceFileType;
    }

    /**
     * This method should be used to process the data and populate the Magento Database.
     *
     * @param $data
     * @return void
     */
    abstract protected function processData($data = null);
}
