<?php

namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Api\FileComponentInterface;
use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Exception\ComponentException;
use Magento\Framework\Exception\LocalizedException;
use Magento\TaxImportExport\Model\Rate\CsvImportHandler;

class TaxRates implements FileComponentInterface
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
     * @throws LocalizedException
     */
    public function execute($data = null)
    {
        try {
            $filePath =  BP . '/' . $data;
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

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
}
