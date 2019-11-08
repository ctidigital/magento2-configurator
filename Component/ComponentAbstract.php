<?php

namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Exception\ComponentException;
use CtiDigital\Configurator\Api\LoggerInterface;
use Magento\Framework\File\Csv;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Symfony\Component\Yaml\Yaml;

abstract class ComponentAbstract
{
    const ENABLED = 1;
    const DISABLED = 0;

    const SOURCE_YAML = 'yaml';
    const SOURCE_CSV = 'csv';
    const SOURCE_JSON = 'json';

    protected $log;
    protected $alias;
    protected $name;
    protected $source;
    protected $parsedData;
    protected $objectManager;
    protected $description = 'Unknown Component';

    /**
     * @var Json
     */
    protected $json;

    public function __construct(
        LoggerInterface $log,
        ObjectManagerInterface $objectManager,
        Json $json
    ) {
        $this->log = $log;
        $this->objectManager = $objectManager;
        $this->json = $json;
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

            // @todo Include some events to dispatch.
//            $this->eventManager->dispatch('configurator_parse_component_before',array('object'=>$this));
//            $this->eventManager->dispatch('configurator_parse_component_before'.$this->alias,array('object'=>$this));

            $this->log->logComment(sprintf("Starting to parse data for %s", $this->getComponentName()));
            $this->parsedData = $this->parseData($this->source);
            $this->log->logComment(sprintf("Finished parsing data for %s", $this->getComponentName()));

//            $this->eventManager->dispatch(
//                'configurator_process_component_before',
//                array('object'=>$this,'source'=>$this->source)
//            );
//            $this->eventManager->dispatch('configurator_process_component_before'.$this->alias,
//                array('object'=>$this,'source'=>$this->source)
//            );

            $this->log->logComment(sprintf("Starting to process data for %s", $this->getComponentName()));
            $this->processData($this->parsedData);
            $this->log->logComment(sprintf("Finished processing data for %s", $this->getComponentName()));

//            $this->eventManager->dispatch('configurator_process_component_after',array('object'=>$this));
//            $this->eventManager->dispatch('configurator_process_component_after'.$this->alias,array('object'=>$this));
        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }
    }

    /**
     * @return true
     */
    public function isSourceRemote($source)
    {
        return (filter_var($source, FILTER_VALIDATE_URL) !== false) ? true : false;
    }

    /**
     * @param $source
     * @return array|bool|float|int|mixed|string|null
     * @throws \Exception
     */
    public function getRemoteData($source)
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $streamContext = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $remoteFile = file_get_contents($source, false, $streamContext);
        return $remoteFile;
    }

    /**
     * This method is used to check whether the data from file or a third party
     * can be parsed and processed. (e.g. does a YAML file exist for it?)
     *
     * This will determine whether the component is enabled or disabled.
     *
     * @return bool
     */
    protected function canParseAndProcess()
    {
        $path = BP . '/' . $this->source;
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        if ($this->isSourceRemote($this->source) === false && !file_exists($path)) {
            throw new ComponentException(
                sprintf("Could not find file in path %s", $path)
            );
        }
        return true;
    }

    /**
     * Whether it be from many files or an external database, parsing (pre-processing)
     * the data is done here.
     *
     * @param $source
     * @return mixed
     */
    protected function parseData($source = null)
    {
        $ext = $this->getExtension($source);
        if ($this->isSourceRemote($source)) {
            $source = $this->getRemoteData($source);
        }
        if ($ext === self::SOURCE_YAML) {
            return $this->parseYamlData($source);
        }
        if ($ext === self::SOURCE_CSV) {
            return $this->parseCsvData($source);
        }
        if ($ext === self::SOURCE_JSON) {
            return $this->parseJsonData($source);
        }
    }

    /**
     * @param $source
     * @return string
     * @throws \Exception
     */
    private function getExtension($source)
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $extension = pathinfo($source, PATHINFO_EXTENSION);
        if (strtolower($extension) === 'yaml') {
            return self::SOURCE_YAML;
        }
        if (strtolower($extension) === 'csv') {
            return self::SOURCE_CSV;
        }
        if (strtolower($extension) === 'json') {
            return self::SOURCE_JSON;
        }
        throw new ComponentException(sprintf('Source "%s" does not have a valid file extension.', $source));
    }

    /**
     * @param $source
     * @return mixed
     */
    private function parseYamlData($source)
    {
        $path = BP . '/' . $source;
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $data = file_get_contents($path);
        return (new Yaml())->parse($data);
    }

    /**
     * @param $source
     * @return array
     * @throws \Exception
     */
    private function parseCsvData($source)
    {
        $file = new File();
        $parser = new Csv($file);

        return $parser->getData($source);
    }

    /**
     * @param $source
     * @return array|bool|float|int|mixed|string|null
     */
    private function parseJsonData($source)
    {
        return $jsonData = $this->json->unserialize($source);
    }

    /**
     * This method should be used to process the data and populate the Magento Database.
     *
     * @param $data
     * @return void
     */
    abstract protected function processData($data = null);
}
