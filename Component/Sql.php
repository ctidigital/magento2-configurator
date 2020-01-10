<?php
/**
 * @package  CtiDigital\Configurator
 * @author Bartosz Herba <bartoszherba@gmail.com>
 * @copyright 2017 CtiDigital
 */

namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Api\ComponentInterface;
use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Component\Processor\SqlSplitProcessor;

/**
 * Class Sql
 */
class Sql implements ComponentInterface
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
     * @var LoggerInterface
     */
    private $log;

    /**
     * Sql constructor.
     * @param SqlSplitProcessor $processor
     * @param LoggerInterface $log
     */
    public function __construct(
        SqlSplitProcessor $processor,
        LoggerInterface $log
    ) {
        $this->processor = $processor;
        $this->log = $log;
    }

    /**
     * This method should be used to process the data and populate the Magento Database.
     *
     * @param mixed $data
     *
     * @return void
     */
    public function execute($data = null)
    {
        if (!isset($data['sql'])) {
            return;
        }

        $this->log->logInfo('Beginning of custom queries configuration:');
        foreach ($data['sql'] as $name => $sqlFile) {
            $path = BP . '/' . $sqlFile;
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            if (false === file_exists($path)) {
                $this->log->logError("{$path} does not exist. Skipping.");
                continue;
            }
            $this->processor->process($name, $path);
        }
    }
}
