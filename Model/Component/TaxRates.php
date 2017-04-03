<?php

namespace CtiDigital\Configurator\Model\Component;

use Magento\Framework\ObjectManagerInterface;
use CtiDigital\Configurator\Model\LoggingInterface;
use Magento\TaxImportExport\Model\Rate\CsvImportHandler;
use CtiDigital\Configurator\Model\Exception\ComponentException;

class TaxRates extends CsvComponentAbstract
{
    protected $alias = 'taxrates';
    protected $name = 'Tax Rates';
    protected $description = 'Component to create Tax Rates';

    /**
     * @var CsvImportHandler
     */
    protected $csvImportHandler;

    /**
     * TaxRules constructor.
     * @param LoggingInterface $log
     * @param ObjectManagerInterface $objectManager
     * @param CsvImportHandler $csvImportHandler
     */
    public function __construct(
        LoggingInterface $log,
        ObjectManagerInterface $objectManager,
        CsvImportHandler $csvImportHandler
    ) {
        parent::__construct($log, $objectManager);
        $this->csvImportHandler = $csvImportHandler;
    }

    /**
     * @param array|null $data
     */
    protected function processData($data = null)
    {
        //Check Row Data exists
        if (!isset($data[0])) {
            throw new ComponentException(
                sprintf('No row data found.')
            );
        }

        try {
            $filePath =  BP . '/' . $this->source;
            $this->log->logComment(
                sprintf('"%s" is being imported', $filePath)
            );

            $this->csvImportHandler->importFromCsvFile(['tmp_name' => $filePath]);
            $this->log->logComment(
                sprintf('Tax Rules import finished')
            );
        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }
    }
}
