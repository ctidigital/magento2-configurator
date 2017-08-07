<?php
namespace CtiDigital\Configurator\Model\Component;

use CtiDigital\Configurator\Model\LoggingInterface;
use CtiDigital\Configurator\Model\Exception\ComponentException;
use Magento\Framework\ObjectManagerInterface;
use FireGento\FastSimpleImport\Model\ImporterFactory;
use Magento\ImportExport\Model\Import;

class Customers extends CsvComponentAbstract
{
    protected $alias = 'customers';
    protected $name = 'Customers';
    protected $description = 'Import customers and addresses';

    protected $requiredColumns = [
        'email',
        '_website',
        '_store',
    ];

    /**
     * @var \Magento\Indexer\Model\IndexerFactory
     */
    protected $indexerFactory;

    /**
     * @var ImporterFactory
     */
    protected $importerFactory;

    /**
     * @var array
     */
    protected $columnHeaders = [];

    public function __construct(
        LoggingInterface $log,
        ObjectManagerInterface $objectManager,
        ImporterFactory $importerFactory,
        \Magento\Indexer\Model\IndexerFactory $indexerFactory
    ) {
        $this->importerFactory = $importerFactory;
        $this->indexerFactory = $indexerFactory;
        parent::__construct($log, $objectManager);
    }

    protected function processData($data = null)
    {
        $this->getColumnHeaders($data);
        unset($data[0]);

        $customerImport = [];

        foreach ($data as $customer) {
            $row = [];
            foreach ($this->getHeaders() as $key => $columnHeader) {
                $row[$columnHeader] = $customer[$key];
            }
            $customerImport[] = $row;
        }

        try {
            /**
             * @var $importer \FireGento\FastSimpleImport\Model\Importer
             */
            $importer = $this->importerFactory->create();
            $importer->setEntityCode('customer_composite');
            $importer->setBehavior(Import::BEHAVIOR_APPEND);
            $importer->processImport($customerImport);
            $this->reindex();
        } catch (\Exception $e) {
            $this->log->logError($e->getMessage());
        }
        $this->log->logInfo($importer->getLogTrace());
        $this->log->logInfo($importer->getErrorMessages());
    }

    /**
     * Check the headers have been set correctly
     *
     * @param $data
     *
     * @return void
     */
    public function getColumnHeaders($data)
    {
        if (!isset($data[0])) {
            throw new ComponentException('No data has been found in the import file');
        }
        foreach ($data[0] as $heading) {
            $this->columnHeaders[] = $heading;
        }
        foreach ($this->requiredColumns as $column) {
            if (!in_array($column, $this->columnHeaders)) {
                throw new ComponentException(sprintf('The column "%s" is required.', $column));
            }
        }
    }

    public function getHeaders()
    {
        return $this->columnHeaders;
    }

    private function reindex()
    {
        $this->log->logInfo('Reindexing the customer grid');
        $customerGrid = $this->indexerFactory->create();
        $customerGrid->load('customer_grid');
        $customerGrid->reindexAll();
    }
}
