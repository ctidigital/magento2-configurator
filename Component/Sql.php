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
use Magento\Framework\Filesystem\DriverInterface;

/**
 * Class Sql - Runs raw SQL queries - generally a fallback for when a configurator component is not available.
 */
class Sql implements ComponentInterface
{
    protected string $alias = 'sql';

    protected string $name = 'Custom Sql';

    protected string $description = 'Component for an execution of custom queries';

    public function __construct(
        private readonly SqlSplitProcessor $processor,
        private readonly LoggerInterface $log,
        private readonly DriverInterface $driver
    ) {}

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
            if (false === $this->driver->isExists($path)) {
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
