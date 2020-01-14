<?php
/**
 * @package  CtiDigital\Configurator
 * @author Bartosz Herba <bartoszherba@gmail.com>
 * @copyright 2017 CtiDigital
 */

namespace CtiDigital\Configurator\Component\Processor;

use CtiDigital\Configurator\Api\LoggerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;

/**
 * Class SqlSplitProcessor
 */
class SqlSplitProcessor
{
    /**
     * @var LoggerInterface
     */
    private $log;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var AdapterInterface
     */
    private $connection;
    /**
     * @param LoggerInterface $log
     * @param ResourceConnection $resource
     */
    public function __construct(
        LoggerInterface $log,
        ResourceConnection $resource
    ) {
        $this->log = $log;
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
    }

    /**
     * @param string $name
     * @param string $filePath
     *
     * return void
     */
    public function process($name, $filePath)
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
     * multi-line queries using preg_match
     *
     * @param string $filePath
     * @param string $delimiter
     *
     * @return array
     */
    private function extractQueriesFromFile($filePath, $delimiter = ';')
    {
        $obBaseLevel = ob_get_level();
        $queries = [];
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $file = fopen($filePath, 'r');
        if (is_resource($file) === true) {
            $query = [];
            while (feof($file) === false) {
                // phpcs:ignore Magento2.Functions.DiscouragedFunction
                $query[] = fgets($file);

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
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        fclose($file);
        return $queries;
    }
}
