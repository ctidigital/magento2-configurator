<?php

namespace CtiDigital\Configurator\Component\Product;

use CtiDigital\Configurator\Component\Products;
use Firegento\FastSimpleImport\Model\Importer;
use FireGento\FastSimpleImport\Model\Adapters\ImportAdapterFactoryInterface;

class Validator
{
    /**
     * Product import attributes which can be nulled if the data is not in a valid format
     */
    const ATTRIBUTES_NULLIFY_ALLOW_LIST = [
        'image',
        'small_image',
        'thumbnail',
        'additional_images',
    ];

    /**
     * Indicate that the row data should be removed entirely
     */
    const IMPORT_DATA_ACTION_REMOVE = 'remove';

    /**
     * Indicate that the attribute that's failing to import should be set to 'null'
     */
    const IMPORT_DATA_ACTION_NULLIFY = 'nullify';

    /**
     * @var ImportAdapterFactoryInterface
     */
    private $importAdapterFactory;

    /**
     * @var array
     */
    private $logs = [];

    private $removedRows = [];

    /**
     * Validator constructor.
     * @param ImportAdapterFactoryInterface $importAdapterFactory
     */
    public function __construct(
        ImportAdapterFactoryInterface $importAdapterFactory
    ) {
        $this->importAdapterFactory = $importAdapterFactory;
    }

    /**
     * @return array
     */
    public function getLogs()
    {
        return $this->logs;
    }

    /**
     * @return array
     */
    public function getRemovedRows()
    {
        return $this->removedRows;
    }

    /**
     * @param $rowData
     * @param $row
     * @param $attributeCode
     * @param $errorMessage
     * @param string $type
     */
    private function writeLog($rowData, $row, $attributeCode, $errorMessage, $type = self::IMPORT_DATA_ACTION_REMOVE)
    {
        $sku = isset($rowData['sku']) ? $rowData['sku'] : null;
        $identifierMessage = ($sku !== null) ? sprintf('SKU: %s', $sku) : sprintf('Row Number : %s', $row);
        switch ($type) {
            case self::IMPORT_DATA_ACTION_NULLIFY:
                $message = sprintf(
                    '%s Error: %s Resolution: Unset the value for attribute code %s',
                    $identifierMessage,
                    $errorMessage,
                    $attributeCode
                );
                break;
            default:
                $message = sprintf(
                    '%s Error: %s Resolution: Removed the row due to error with attribute code %s',
                    $identifierMessage,
                    $errorMessage,
                    $attributeCode
                );
                $this->removedRows[$row] = $message;
                break;
        }
        $this->logs[] = $message;
    }

    /**
     * Runs the import data through the validation steps and returns the modified values
     *
     * @param Importer $import
     * @param $importLines
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getValidatedImport(Importer $import, $importLines)
    {
        $this->logs = [];
        $failedImportRows = $this->getImportRowFailures($import, $importLines);
        $importLines = $this->omitItemsFromImport($importLines, $failedImportRows);
        return $importLines;
    }

    /**
     * @param Importer $import
     * @param $importLines
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getImportRowFailures(Importer $import, $importLines)
    {
        $failedRows = [];
        // Creates a validation model and runs the import data through so we can find which rows would fail
        $validation = $import->createImportModel();
        $validationSource = $this->importAdapterFactory->create([
            'data' => $importLines,
            'multipleValueSeparator' => Products::SEPARATOR
        ]);
        $validation->validateSource($validationSource);
        $errors = $validation->getErrorAggregator();
        foreach ($errors->getRowsGroupedByErrorCode() as $error => $rows) {
            if (is_array($rows)) {
                $failedRow = $this->formatRowRemoveData($error, $rows);
                $failedRows[] = $failedRow;
            }
        }
        return $failedRows;
    }

    /**
     * Either removes a row entirely or nulls specific attributes that we know are okay to ignore
     *
     * @param array $importLines
     * @param array $failedRows
     * @return array
     */
    public function omitItemsFromImport(array $importLines, array $failedRows)
    {
        foreach ($failedRows as $failedRow) {
            $attributeCode = $failedRow['attribute_code'];
            foreach ($failedRow['rows'] as $row) {
                switch ($failedRow['action']) {
                    case self::IMPORT_DATA_ACTION_NULLIFY:
                        if (isset($importLines[$row][$attributeCode])) {
                            $this->writeLog(
                                $importLines[$row],
                                $row,
                                $attributeCode,
                                $failedRow['message'],
                                self::IMPORT_DATA_ACTION_NULLIFY
                            );
                            $importLines[$row][$attributeCode] = null;
                        }
                        break;
                    default:
                        if (isset($importLines[$row])) {
                            $this->writeLog(
                                $importLines[$row],
                                $row,
                                $attributeCode,
                                $failedRow['message'],
                                self::IMPORT_DATA_ACTION_REMOVE
                            );
                            unset($importLines[$row]);
                        }
                }
            }
        }
        $importLines = array_values($importLines);
        return $importLines;
    }

    /**
     * Gets the attribute code from the error returned by the validator
     *
     * @param $error
     * @return string|null
     */
    public function getAttributeCodeFromError($error)
    {
        $matches = [];
        $attributeCode = null;
        preg_match('/attribute\s([^\s]*)/', (string) $error, $matches);
        if (isset($matches[1])) {
            $attributeCode = $matches[1];
        }
        return $attributeCode;
    }

    /**
     * Processes the error into a set format
     *
     * @param $error
     * @param $rows
     * @return array
     */
    private function formatRowRemoveData($error, $rows)
    {
        // Magento increases the row number by 1 as it assumes you've uploaded a CSV file with a header
        $rowsProcessed = array_map(function ($row) {
            return $row - 1;
        }, $rows);

        $attributeCode = $this->getAttributeCodeFromError($error);
        $action = (in_array($attributeCode, self::ATTRIBUTES_NULLIFY_ALLOW_LIST)) ?
            self::IMPORT_DATA_ACTION_NULLIFY :
            self::IMPORT_DATA_ACTION_REMOVE;

        return [
            'action' => $action,
            'message' => $error,
            'rows' => $rowsProcessed,
            'attribute_code' => $attributeCode
        ];
    }
}
