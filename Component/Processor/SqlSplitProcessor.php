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
     * @param string $fileContent
     *
     * return void
     */
    public function process($name, $fileContent)
    {
        $this->log->logInfo("- Processing file '$name'");

        $queries = $this->extractQueriesFromFile($fileContent);

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
     * Split file content string into separate queries
     *
     * @param string $fileContent
     *
     * @return array
     */
    private function extractQueriesFromFile($fileContent)
    {
        return preg_split("/\\r\\n|\\r|\\n/", $fileContent, -1, PREG_SPLIT_NO_EMPTY);
    }
}
