<?php

namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Api\ComponentInterface;
use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Exception\ComponentException;
use Magento\TaxImportExport\Model\Rate\CsvImportHandler;

class TaxRates implements ComponentInterface
{
    protected $alias = 'taxrates';
    protected $name = 'Tax Rates';
    protected $description = 'Component to create Tax Rates';

    /**
     * @var CsvImportHandler
     */
    protected $csvImportHandler;

    /**
     * @var LoggerInterface
     */
    private $log;

    /**
     * TaxRates constructor.
     * @param CsvImportHandler $csvImportHandler
     * @param LoggerInterface $log
     */
    public function __construct(
        CsvImportHandler $csvImportHandler,
        LoggerInterface $log
    ) {
        $this->csvImportHandler = $csvImportHandler;
        $this->log = $log;
    }

    /**
     * @param null $data
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute($data = null)
    {
        //Check Row Data exists
        if (!isset($data[0])) {
            throw new ComponentException(
                sprintf('No row data found.')
            );
        }

        try {
            $filePath =  BP . '/' . $this->source;
            $this->log->logInfo(
                sprintf('"%s" is being imported', $filePath)
            );

            $this->csvImportHandler->importFromCsvFile(['tmp_name' => $filePath]);
            $this->log->logInfo(
                sprintf('"%s" Tax Rules import finished', $filePath)
            );
        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }
    }
}
