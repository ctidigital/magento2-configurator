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
            // Sort data into order importExport requires
            $sortedData = $this->getSortedData($data);

            // Generate sorted csv file
            $tmpFile = $this->getTmpFile($sortedData);

            // Pass the temporary file name to the import handler
            $this->csvImportHandler->importFromCsvFile(['tmp_name' => $tmpFile]);

            // Remove the temporary file
            unlink($tmpFile);

            // We don't know how many were successfully imported
            // so we can't log the number of records imported, but we can log that the import was successful
            // we could diff the count of the state before and after but it would be expensive
            $this->log->logInfo(
                sprintf('Tax rates finished importing, check the rates in the admin panel.')
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

    /**
     * @param array $data
     * @return array
     */
    protected function getSortedData(array $data): array
    {
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
        return $sortedData;
    }

    /**
     * @param array $sortedData
     * @return string
     */
    protected function getTmpFile(array $sortedData): string
    {
        // Define a temporary file name
        $tmpFile = sys_get_temp_dir() . '/tax_rates_' . uniqid() . '.csv';

        // Write the CSV data to the temporary file
        $fileHandle = fopen($tmpFile, 'w');
        foreach ($sortedData as $line) {
            fputcsv($fileHandle, $line, escape: '');
        }
        // close stream
        fclose($fileHandle);

        // Return the path to the temporary file
        return $tmpFile;
    }
}
