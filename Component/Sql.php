<?php
declare(strict_types=1);

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
 * Class Sql - Runs raw SQL queries - generally a fallback for when a configurator component is not available.
 */
class Sql implements ComponentInterface
{
    protected string $alias = 'sql';

    protected string $name = 'Custom Sql';

    protected string $description = 'Component for an execution of custom queries';

    private SqlSplitProcessor $processor;

    private LoggerInterface $log;

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
     */
    public function execute(mixed $data = null): void
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

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
