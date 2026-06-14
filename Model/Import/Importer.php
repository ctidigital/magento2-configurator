<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Model\Import;

use CtiDigital\Configurator\Api\ImportAdapterFactoryInterface;
use CtiDigital\Configurator\Exception\ComponentException;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\ImportExport\Model\ImportFactory;

/**
 * Runs Magento's native ImportExport engine against an in-memory array source.
 *
 * Import defaults are baked in here as constants — notably the catalog_product
 * entity, which the Products component relies on without setting one explicitly.
 */
class Importer
{
    private const DEFAULT_ENTITY              = 'catalog_product';
    private const DEFAULT_BEHAVIOR            = Import::BEHAVIOR_APPEND;
    private const DEFAULT_VALIDATION_STRATEGY = ProcessingErrorAggregatorInterface::VALIDATION_STRATEGY_STOP_ON_ERROR;
    private const DEFAULT_ALLOWED_ERROR_COUNT = 0;

    private ?Import $importModel = null;

    private ?bool $validationResult = null;

    private string $logTrace = '';

    /** @var array<int, string> */
    private array $errorMessages = [];

    /** @var array<string, mixed> */
    private array $settings;

    public function __construct(
        private readonly ImportFactory $importModelFactory,
        private readonly ImportErrorService $importErrorService,
        private ImportAdapterFactoryInterface $importAdapterFactory
    ) {
        $this->settings = [
            'entity'                           => self::DEFAULT_ENTITY,
            'behavior'                         => self::DEFAULT_BEHAVIOR,
            'ignore_duplicates'                => false,
            'validation_strategy'              => self::DEFAULT_VALIDATION_STRATEGY,
            'allowed_error_count'              => self::DEFAULT_ALLOWED_ERROR_COUNT,
            '_import_multiple_value_separator' => Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR,
        ];
    }

    /**
     * Validate then import the given rows, aggregating any errors.
     *
     * @param array<int, array<string, mixed>> $dataArray
     * @throws ComponentException
     */
    public function processImport(array $dataArray): void
    {
        $this->resetImportModel();
        $this->validateData($dataArray);
        $this->resetImportModel();
        $this->importData();
    }

    /**
     * @param array<int, array<string, mixed>> $dataArray
     * @throws ComponentException
     */
    private function validateData(array $dataArray): void
    {
        $importModel = $this->getImportModel();
        $source = $this->importAdapterFactory->create([
            'data'                   => $dataArray,
            'multipleValueSeparator' => $this->getMultipleValueSeparator(),
        ]);
        $this->validationResult = $importModel->validateSource($source);
        $errorAggregator = $this->getErrorAggregator();
        if ($errorAggregator->hasToBeTerminated()) {
            throw new ComponentException(
                $this->importErrorService->getImportErrorMessagesAsString($errorAggregator)
            );
        }
    }

    /**
     * Import the rows persisted to importexport_importdata during validation.
     *
     * @throws ComponentException
     */
    private function importData(): void
    {
        $importModel = $this->getImportModel();
        $importModel->importSource();
        $this->logTrace = $importModel->getFormatedLogTrace();
        $this->handleImportResult($importModel);
    }

    /**
     * @throws ComponentException
     */
    private function handleImportResult(Import $importModel): void
    {
        $errorAggregator = $this->getErrorAggregator();
        $this->errorMessages = $this->importErrorService->getImportErrorMessages($errorAggregator);
        if (!$errorAggregator->hasToBeTerminated()) {
            $importModel->invalidateIndex();
            return;
        }
        throw new ComponentException(
            $this->importErrorService->getImportErrorMessagesAsString($errorAggregator)
        );
    }

    public function getImportModel(): Import
    {
        if ($this->importModel === null) {
            $this->importModel = $this->importModelFactory->create();
            $this->importModel->setData($this->settings);
        }
        return $this->importModel;
    }

    private function resetImportModel(): void
    {
        $this->importModel = null;
    }

    public function getErrorAggregator(): ProcessingErrorAggregatorInterface
    {
        return $this->getImportModel()->getErrorAggregator();
    }

    public function setEntityCode(string $entityCode): self
    {
        $this->assertImportModelNotInitialized();
        $this->settings['entity'] = $entityCode;
        return $this;
    }

    public function setBehavior(string $behavior): self
    {
        $this->assertImportModelNotInitialized();
        $this->settings['behavior'] = $behavior;
        return $this;
    }

    public function setIgnoreDuplicates(bool $value): self
    {
        $this->assertImportModelNotInitialized();
        $this->settings['ignore_duplicates'] = $value;
        return $this;
    }

    public function setValidationStrategy(string $strategy): self
    {
        $this->assertImportModelNotInitialized();
        $this->settings['validation_strategy'] = $strategy;
        return $this;
    }

    public function setAllowedErrorCount(int $count): self
    {
        $this->assertImportModelNotInitialized();
        $this->settings['allowed_error_count'] = $count;
        return $this;
    }

    public function setMultipleValueSeparator(string $multipleValueSeparator): self
    {
        $this->assertImportModelNotInitialized();
        $this->settings['_import_multiple_value_separator'] = $multipleValueSeparator;
        return $this;
    }

    public function getValidationResult(): ?bool
    {
        return $this->validationResult;
    }

    public function getLogTrace(): string
    {
        return $this->logTrace;
    }

    /**
     * @return array<int, string>
     */
    public function getErrorMessages(): array
    {
        return $this->errorMessages;
    }

    public function getMultipleValueSeparator(): string
    {
        return $this->settings['_import_multiple_value_separator'];
    }

    /**
     * @throws ComponentException
     */
    private function assertImportModelNotInitialized(): void
    {
        if ($this->importModel !== null) {
            throw new ComponentException(
                (string) __('Import settings cannot be changed after the import model is initialized.')
            );
        }
    }
}
