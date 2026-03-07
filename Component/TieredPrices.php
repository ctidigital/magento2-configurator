<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Api\ComponentInterface;
use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Component\Product\AttributeOption;
use CtiDigital\Configurator\Exception\ComponentException;
use FireGento\FastSimpleImport\Model\ImporterFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TieredPrices implements ComponentInterface
{
    const SKU_COLUMN_HEADING = 'sku';
    const SEPARATOR = ';';

    protected string $alias = 'tiered_prices';
    protected string $name = 'Tiered Prices';
    protected string $description = 'Component to import tiered prices using a CSV file.';

    private array $successPrices = [];

    private array $skippedPrices = [];

    private int|false $skuColumn;

    public function __construct(
        protected readonly ImporterFactory $importerFactory,
        protected readonly AttributeOption $attributeOption,
        private readonly LoggerInterface $log
    ) {}

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute(mixed $data = null): void
    {
        // Get the first row of the CSV file for the attribute columns.
        if (!isset($data[0])) {
            throw new ComponentException(
                sprintf('The row data is not valid.')
            );
        }
        $attributeKeys = $this->getAttributesFromCsv($data);
        $this->skuColumn = $this->getSkuColumnIndex($attributeKeys);
        $totalColumnCount = count($attributeKeys);
        unset($data[0]);

        $pricesArray = [];

        foreach ($data as $tieredPrice) {
            if (count($tieredPrice) !== $totalColumnCount) {
                $this->skippedPrices[] = $tieredPrice[$this->skuColumn];
                continue;
            }
            $priceArray = [];
            foreach ($attributeKeys as $column => $code) {
                $priceArray[$code] = $tieredPrice[$column];
                $this->attributeOption->processAttributeValues($code, $priceArray[$code]);
            }
            $pricesArray[] = $priceArray;
            $this->successPrices[] = $tieredPrice[$this->skuColumn];
        }

        if (count($this->skippedPrices) > 0) {
            $this->log->logInfo(
                sprintf(
                    'The following tiered prices were skipped as they do not have the required columns: '
                    .PHP_EOL.'%s',
                    implode(PHP_EOL, $this->skippedPrices)
                )
            );
        }

        $this->log->logInfo(sprintf('Attempting to import %s rows', count($this->successPrices)));
        try {
            $import = $this->importerFactory->create();
            $import->setEntityCode('advanced_pricing');
            $import->setMultipleValueSeparator(self::SEPARATOR);
            $import->processImport($pricesArray);
        } catch (\Exception $e) {
            $this->log->logError($e->getMessage());
        }
        $this->log->logInfo($import->getLogTrace());
        $this->log->logError($import->getErrorMessages());
    }

    /**
     * Gets the first row of the CSV file as these should be the attribute keys
     *
     * @param null $data
     */
    public function getAttributesFromCsv($data = null): array
    {
        $attributes = [];
        foreach ($data[0] as $attributeCode) {
            $attributes[] = $attributeCode;
        }
        return $attributes;
    }

    /**
     * Get the column index of the SKU
     */
    public function getSkuColumnIndex(array $headers): int|false
    {
        return array_search(self::SKU_COLUMN_HEADING, $headers);
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
