<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Component\Product;

use CtiDigital\Configurator\Component\Products;
use CtiDigital\Configurator\Model\Import\Importer;
use CtiDigital\Configurator\Api\ImportAdapterFactoryInterface;

class Validator
{
    /**
     * Product import attributes which can be nulled if the data is not in a valid format.
     */
    const ATTRIBUTES_NULLIFY_ALLOW_LIST = [
        'image',
        'small_image',
        'thumbnail',
        'additional_images',
    ];

    /**
     * Indicate that the row data should be removed entirely.
     */
    const IMPORT_DATA_ACTION_REMOVE = 'remove';

    /**
     * Indicate that the attribute that's failing to import should be set to 'null'.
     */
    const IMPORT_DATA_ACTION_NULLIFY = 'nullify';

    private array $logs = [];

    private array $removedRows = [];

    public function __construct(
        private readonly ImportAdapterFactoryInterface $importAdapterFactory
    ) {}

    public function getLogs(): array
    {
        return $this->logs;
    }

    public function getRemovedRows(): array
    {
        return $this->removedRows;
    }

    private function writeLog(array $rowData, mixed $row, mixed $attributeCode, string $errorMessage, string $type = self::IMPORT_DATA_ACTION_REMOVE): void
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
     * Runs the import data through the validation steps and returns the modified values.
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getValidatedImport(Importer $import, mixed $importLines): array
    {
        $this->logs = [];
        $failedImportRows = $this->getImportRowFailures($import, $importLines);
        $importLines = $this->omitItemsFromImport($importLines, $failedImportRows);
        return $importLines;
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getImportRowFailures(Importer $import, mixed $importLines): array
    {
        $failedRows = [];
        // Creates a validation model and runs the import data through so we can find which rows would fail
        $validation = $import->getImportModel();
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
     * Either removes a row entirely or nulls specific attributes that we know are okay to ignore.
     */
    public function omitItemsFromImport(array $importLines, array $failedRows): array
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
     * Gets the attribute code from the error returned by the validator.
     */
    public function getAttributeCodeFromError(mixed $error): ?string
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
     * Processes the error into a set format.
     */
    private function formatRowRemoveData(mixed $error, array $rows): array
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
