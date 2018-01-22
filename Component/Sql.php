<?php
/**
 * @package  CtiDigital\Configurator
 * @author Bartosz Herba <bartoszherba@gmail.com>
 * @copyright 2017 CtiDigital
 */

namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Component\Processor\SqlSplitProcessor;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class Sql
 */
class Sql extends YamlComponentAbstract
{
    /**
     * @var string
     */
    protected $alias = 'sql';

    /**
     * @var string
     */
    protected $name = 'Custom Sql';

    /**
     * @var string
     */
    protected $description = 'Component for an execution of custom queries';

    /**
     * @var SqlSplitProcessor
     */
    private $processor;

    /**
     * {@inheritdoc}
     *
     * @param LoggerInterface $log
     * @param ResourceConnection $resource
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        LoggerInterface $log,
        ObjectManagerInterface $objectManager,
        SqlSplitProcessor $processor
    ) {
        parent::__construct($log, $objectManager);

        $this->processor = $processor;
    }

    /**
     * This method should be used to process the data and populate the Magento Database.
     *
     * @param mixed $data
     *
     * @return void
     */
    protected function processData($data = null)
    {
        if (!isset($data['sql'])) {
            return;
        }

        $this->log->logInfo('Beginning of custom queries configuration:');
        foreach ($data['sql'] as $name => $sqlFile) {
            $path = BP . '/' . $sqlFile;
            if (false === file_exists($path)) {
                $this->log->logError("{$path} does not exist. Skipping.");
                continue;
            }
            $fileContent = file_get_contents($path);
            $this->processor->process($name, $fileContent);
        }
    }
}
