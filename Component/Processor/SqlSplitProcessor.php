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
     * Split file content string into separate queries, allowing for
     * multi-line queries using preg_match.
     */
    private function extractQueriesFromFile(string $filePath, string $delimiter = ';'): array
    {
        $obBaseLevel = ob_get_level();
        $queries = [];
        $file = $this->driver->fileOpen($filePath, 'r');
        if (is_resource($file) === true) {
            $query = [];
            while ($this->driver->endOfFile($file) === false) {
                $query[] = $this->driver->fileReadLine($file, 4096);

                if (preg_match('~' . preg_quote($delimiter, '~') . '\s*$~iS', end($query)) === 1) {
                    $query = trim(implode('', $query));

                    $queries[] = $query;

                    while (ob_get_level() > $obBaseLevel) {
                        ob_end_flush();
                    }
                    flush();
                }

                if (is_string($query) === true) {
                    $query = [];
                }
            }
        }
        $this->driver->fileClose($file);
        return $queries;
    }
}
