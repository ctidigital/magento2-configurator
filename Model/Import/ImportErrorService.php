<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Model\Import;

use Magento\ImportExport\Model\Import\Entity\AbstractEntity;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingError;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;

/**
 * Formats a native import error aggregator into human-readable messages.
 */
class ImportErrorService
{
    private const LIMIT_ERRORS_MESSAGE = 100;

    public function getImportErrorMessagesAsString(ProcessingErrorAggregatorInterface $errorAggregator): string
    {
        return implode("\n", $this->getImportErrorMessages($errorAggregator));
    }

    /**
     * @return array<int, string>
     */
    public function getImportErrorMessages(ProcessingErrorAggregatorInterface $errorAggregator): array
    {
        $result = [];
        if ($errorAggregator->getErrorsCount()) {
            $counter = 0;
            foreach ($this->getErrorMessages($errorAggregator) as $errorMessage) {
                $result[] = $errorMessage;
                if ($counter >= self::LIMIT_ERRORS_MESSAGE) {
                    $result[] = __('Aborted after %1 errors.', self::LIMIT_ERRORS_MESSAGE);
                    break;
                }
                $counter++;
            }
            if ($errorAggregator->hasFatalExceptions()) {
                foreach ($this->getSystemExceptions($errorAggregator) as $processingError) {
                    $result[] = $processingError->getErrorMessage() .
                        __('Additional data') . ': ' . $processingError->getErrorDescription();
                }
            }
        }

        return $result;
    }

    /**
     * @return array<int, string>
     */
    private function getErrorMessages(ProcessingErrorAggregatorInterface $errorAggregator): array
    {
        $messages = [];
        $rowMessages = $errorAggregator->getRowsGroupedByErrorCode([], [AbstractEntity::ERROR_CODE_SYSTEM_EXCEPTION]);
        foreach ($rowMessages as $errorCode => $rows) {
            $messages[] = $errorCode . ' ' . __('in rows:') . ' ' . implode(', ', $rows);
        }
        return $messages;
    }

    /**
     * @return array<int, ProcessingError>
     */
    private function getSystemExceptions(ProcessingErrorAggregatorInterface $errorAggregator): array
    {
        return $errorAggregator->getErrorsByCode([AbstractEntity::ERROR_CODE_SYSTEM_EXCEPTION]);
    }
}
