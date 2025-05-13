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

            $sortedData = [];

            foreach ($data as $index => $rate) {
                if ($index === 0) {
                    $sortedData[] = $rate;
                    continue; // Skip the header row
                }

                $relativeData = array_combine($data[0], $rate);

                // Reorder the data to match the expected format
                // Why this is a requirement is not clear
                $rateData = [
                    $relativeData['code'],
                    $relativeData['tax_country_id'],
                    $relativeData['tax_region_id'],
                    $relativeData['tax_postcode'],
                    $relativeData['rate'],
                    $relativeData['zip_is_range'],
                    $relativeData['zip_from'],
                    $relativeData['zip_to']
                ];
                $sortedData[] = $rateData;
            }

            // Define a temporary file name
            $tmpFilePath = sys_get_temp_dir() . '/tax_rates_' . uniqid() . '.csv';

            // Write the CSV data to the temporary file
            $fileHandle = fopen($tmpFilePath, 'w');
            foreach ($sortedData as $line) {
                fputcsv($fileHandle, $line, escape: '');
            }
            fclose($fileHandle);

            // Pass the temporary file to the import handler
            $this->csvImportHandler->importFromCsvFile(['tmp_name' => $tmpFilePath]);

            // Remove the temporary file
            unlink($tmpFilePath);

            $this->log->logInfo(
                sprintf('%s tax rates finished importing.', count($data))
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
