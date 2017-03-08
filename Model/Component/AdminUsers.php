<?php
namespace CtiDigital\Configurator\Model\Component;

use CtiDigital\Configurator\Model\Exception\ComponentException;
use CtiDigital\Configurator\Model\LoggingInterface;
use Magento\Framework\ObjectManagerInterface;
use Symfony\Component\Yaml\Yaml;

class AdminUsers extends YamlComponentAbstract
{
    protected $alias = 'adminusers';
    protected $name = 'Admin Users';
    protected $description = 'Component to create Admin Users';


    public function __construct(LoggingInterface $log, ObjectManagerInterface $objectManager)
    {
        parent::__construct($log, $objectManager);

    }

    /**
     * @param array $data
     * @SuppressWarnings(PHPMD)
     */
    protected function processData($data = null)
    {
        try {

        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }
    }
}
