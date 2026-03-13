<?php
declare(strict_types=1);

/**
 * @package  CtiDigital\Configurator
 * @author Bartosz Herba <bartoszherba@gmail.com>
 * @copyright 2017 CtiDigital
 */

namespace CtiDigital\Configurator\Component\Processor;

use CtiDigital\Configurator\Api\LoggerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Filesystem\DriverInterface;

class SqlSplitProcessor
{
    private readonly AdapterInterface $connection;

    public function __construct(
        private readonly LoggerInterface $log,
        private readonly ResourceConnection $resource,
        private readonly DriverInterface $driver
    ) {
        $this->connection = $resource->getConnection();
    }

    /**
     * Process a named SQL file, executing each statement in a transaction.
     */
    public function process(string $name, string $filePath): void
    {
        $this->log->logInfo("- Processing file '$name'");

        $queries = $this->extractQueriesFromFile($filePath);

        $totalSqlCnt = count($queries);
        $cnt = 1;

        if ($totalSqlCnt === 0) {
            $this->log->logInfo('No queries has been found in file.');

            return;
        }

        $this->connection->beginTransaction();

        try {
            foreach ($queries as $query) {
                $this->log->logComment($query, 1);
                $this->connection->query($query);
                $this->log->logInfo("[{$cnt}/$totalSqlCnt] queries executed.", 1);
                $cnt++;
            }

            $this->connection->commit();
        } catch (\Exception $ex) {
            $this->log->logError($ex->getMessage());
            $this->connection->rollBack();
        }
    }

    /**
     * Read the entire file and split it into individual queries on the delimiter.
     * Using fileGetContents avoids relying on fileReadLine line-splitting behaviour,
     * which varies across Magento / Mage-OS framework versions.
     */
    private function extractQueriesFromFile(string $filePath, string $delimiter = ';'): array
    {
        $content = $this->driver->fileGetContents($filePath);
        $queries = [];
        foreach (explode($delimiter, $content) as $statement) {
            $statement = trim($statement);
            if ($statement !== '') {
                $queries[] = $statement . $delimiter;
            }
        }
        return $queries;
    }
}
