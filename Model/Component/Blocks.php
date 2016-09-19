<?php

namespace CtiDigital\Configurator\Model\Component;

use CtiDigital\Configurator\Model\Exception\ComponentException;
use CtiDigital\Configurator\Model\LoggingInterface;
use Magento\Framework\ObjectManagerInterface;
use Symfony\Component\Yaml\Yaml;

class Blocks extends ComponentAbstract
{

    protected $alias = 'blocks';
    protected $name = 'Blocks';
    protected $description = 'Component to create/maintain blocks.';

    /**
     * @var \Magento\Cms\Model\BlockFactory
     */
    protected $blockFactory;

    public function __construct(LoggingInterface $log, ObjectManagerInterface $objectManager)
    {
        parent::__construct($log, $objectManager);
        $this->blockFactory = $this->objectManager->create(\Magento\Cms\Model\BlockFactory::class);
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

    /**
     * @param array $data
     */
    protected function processData($data = null)
    {
        try {

            foreach ($data as $identifier => $data) {
                $this->processBlock($identifier, $data);
            }

        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }
    }

    private function processBlock($identifier, $data)
    {
        try {
            
        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }
    }

}
