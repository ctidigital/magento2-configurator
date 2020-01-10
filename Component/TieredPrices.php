<?php

namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Api\ComponentInterface;
use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Component\Product\AttributeOption;
use CtiDigital\Configurator\Exception\ComponentException;
use FireGento\FastSimpleImport\Model\ImporterFactory;

/**
 * Class Products
 * @package CtiDigital\Configurator\Model\Component
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TieredPrices implements ComponentInterface
{
    const SKU_COLUMN_HEADING = 'sku';
    const SEPARATOR = ';';

    protected $alias = 'tiered_prices';
    protected $name = 'Tiered Prices';
    protected $description = 'Component to import tiered prices using a CSV file.';

    /**
     * @var ImporterFactory
     */
    protected $importerFactory;

    /**
     * @var AttributeOption
     */
    protected $attributeOption;

    /**
     * @var LoggerInterface
     */
    private $log;

    /**
     * @var []
     */
    private $successPrices;

    /**
     * @var []
     */
    private $skippedPrices;

    /**
     * @var int
     */
    private $skuColumn;

    /**
     * TieredPrices constructor.
     * @param ImporterFactory $importerFactory
     * @param AttributeOption $attributeOption
     * @param LoggerInterface $log
     */
    public function __construct(
        ImporterFactory $importerFactory,
        AttributeOption $attributeOption,
        LoggerInterface $log
    ) {
        $this->importerFactory = $importerFactory;
        $this->attributeOption = $attributeOption;
        $this->log = $log;
    }

    /**
     * @param null $data
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute($data = null)
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
     * @return array
     */
    public function getAttributesFromCsv($data = null)
    {
        $attributes = [];
        foreach ($data[0] as $attributeCode) {
            $attributes[] = $attributeCode;
        }
        return $attributes;
    }

    /**
     * Get the column index of the SKU
     *
     * @param $headers
     *
     * @return mixed
     */
    public function getSkuColumnIndex($headers)
    {
        return array_search(self::SKU_COLUMN_HEADING, $headers);
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
