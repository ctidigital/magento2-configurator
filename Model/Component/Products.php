<?php
namespace CtiDigital\Configurator\Model\Component;

use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\ObjectManagerInterface;
use CtiDigital\Configurator\Model\LoggingInterface;
use CtiDigital\Configurator\Model\Component\Product\Image;
use CtiDigital\Configurator\Model\Component\Product\AttributeOption;
use FireGento\FastSimpleImport\Model\ImporterFactory;
use CtiDigital\Configurator\Model\Exception\ComponentException;

/**
 * Class Products
 * @package CtiDigital\Configurator\Model\Component
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Products extends CsvComponentAbstract
{
    const SKU_COLUMN_HEADING = 'sku';
    const QTY_COLUMN_HEADING = 'qty';
    const IS_IN_STOCK_COLUMN_HEADING = 'is_in_stock';
    const SEPARATOR = ';';

    protected $alias = 'products';
    protected $name = 'Products';
    protected $description = 'Component to import products using a CSV file.';

    protected $imageAttributes = [
        'image',
        'small_image',
        'thumbnail',
        'media_image'
    ];

    /**
     * The attributes that may use ',' as the separator and need replacing
     *
     * @var array
     */
    protected $attrSeparator = [
        'product_websites',
        'store_view_code'
    ];

    /**
     * @var ImporterFactory
     */
    protected $importerFactory;

    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var Image
     */
    protected $image;

    /**
     * @var AttributeOption
     */
    protected $attributeOption;

    /**
     * @var []
     */
    private $successProducts;

    /**
     * @var []
     */
    private $skippedProducts;

    /**
     * @var int
     */
    private $skuColumn;

    /**
     * Products constructor.
     *
     * @param LoggingInterface $log
     * @param ObjectManagerInterface $objectManager
     * @param ImporterFactory $importerFactory
     * @param ProductFactory $productFactory
     * @param Image $image
     * @param AttributeOption $attributeOption
     */
    public function __construct(
        LoggingInterface $log,
        ObjectManagerInterface $objectManager,
        ImporterFactory $importerFactory,
        ProductFactory $productFactory,
        Image $image,
        AttributeOption $attributeOption
    ) {
        parent::__construct($log, $objectManager);
        $this->productFactory= $productFactory;
        $this->importerFactory = $importerFactory;
        $this->image = $image;
        $this->attributeOption = $attributeOption;
    }

    /**
     * @param null $data
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function processData($data = null)
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

        // Prepare the data
        $productsArray = array();

        foreach ($data as $product) {
            if (count($product) !== $totalColumnCount) {
                $this->skippedProducts[] = $product[$this->skuColumn];
                continue;
            }
            $productArray = array();
            foreach ($attributeKeys as $column => $code) {
                $product[$column] = $this->clean($product[$column], $code);
                if (in_array($code, $this->imageAttributes)) {
                    $product[$column] = $this->image->getImage($product[$column]);
                }
                $productArray[$code] = $product[$column];
                $this->attributeOption->processAttributeValues($code, $productArray[$code]);
            }
            if ($this->isConfigurable($productArray)) {
                $variations = $this->constructConfigurableVariations($productArray);
                if (strlen($variations) > 0) {
                    $productArray['configurable_variations'] = $variations;
                }
                unset($productArray['associated_products']);
                unset($productArray['configurable_attributes']);
            }
            if ($this->isStockSpecified($productArray) === false) {
                $productArray = $this->setStock($productArray);
            }
            $productsArray[] = $productArray;
            $this->successProducts[] = $product[$this->skuColumn];
        }
        if (count($this->skippedProducts) > 0) {
            $this->log->logInfo(
                sprintf(
                    'The following products were skipped as they do not have the required columns: ' . PHP_EOL . '%s',
                    implode(PHP_EOL, $this->skippedProducts)
                )
            );
        }
        $this->attributeOption->saveOptions();
        $this->log->logInfo(sprintf('Attempting to import %s rows', count($this->successProducts)));
        try {
            $import = $this->importerFactory->create();
            $import->setMultipleValueSeparator(self::SEPARATOR);
            $import->processImport($productsArray);
        } catch (\Exception $e) {

        }
        $this->log->logInfo($import->getLogTrace());
        $this->log->logError($import->getErrorMessages());
    }

    /**
     * Gets the file extension
     *
     * @param null $source
     * @return mixed
     */
    public function getFileType ($source = null)
    {
        // Get the file extension so we know how to load the file
        $sourceFileInfo = pathinfo($source);
        if (!isset($sourceFileInfo['extension'])) {
            throw new ComponentException(
                sprintf('Could not find a valid extension for the source file.')
            );
        }
        $fileType = $sourceFileInfo['extension'];
        return $fileType;
    }

    /**
     * Gets the first row of the CSV file as these should be the attribute keys
     *
     * @param null $data
     * @return array
     */
    public function getAttributesFromCsv ($data = null)
    {
        $attributes = array();
        foreach ($data[0] as $attributeCode) {
            $attributes[] = $attributeCode;
        }
        return $attributes;
    }

    /**
     * Test if a product is a configurable
     *
     * @param array $data
     * @return bool
     */
    public function isConfigurable ($data = array())
    {
        if (isset($data['product_type']) && $data['product_type'] === 'configurable') {
            return true;
        }
        return false;
    }

    /**
     * Create the configurable product string
     *
     * @param $data
     * @return string
     */
    public function constructConfigurableVariations ($data)
    {
        $variations = '';
        if (isset($data['associated_products']) && isset($data['configurable_attributes'])) {
            $products = explode(',', $data['associated_products']);
            $attributes = explode(',', $data['configurable_attributes']);

            if (is_array($products) && is_array($attributes)) {
                $productsCount = count($products);
                $count = 0;
                foreach ($products as $sku) {
                    $productModel = $this->productFactory->create();
                    $id = $productModel->getIdBySku($sku);
                    $productModel->load($id);

                    if ($productModel->getId()) {
                        $configSkuAttributes = $this->constructAttributeData($attributes, $productModel);
                        if (strlen($configSkuAttributes) > 0) {
                            $variations .= 'sku=' . $sku . self::SEPARATOR . $configSkuAttributes;
                        }
                        $count++;
                        if ($count < $productsCount) {
                            $variations .= '|';
                        }
                    }
                }
            }
        }
        return $variations;
    }

    /**
     * Get the attributes and the values as a string
     *
     * @param array $attributes
     * @param \Magento\Catalog\Model\Product $productModel
     * @return string
     */
    public function constructAttributeData (array $attributes, \Magento\Catalog\Model\Product $productModel)
    {
        $skuAttributes = '';
        $attrCounter = 0;
        foreach ($attributes as $attributeCode) {
            $attrCounter++;
            if ($productModel->hasData($attributeCode) == false) {
                $this->log->logError(
                    sprintf(
                        'The product "%s" is missing an attribute value for "%s" and will not be added ' .
                        'to the configurable product',
                        $productModel->getSku(),
                        $attributeCode
                    )
                );
                // Unset any previous attributes.
                $skuAttributes = '';
                break;
            }
            $productAttribute = $productModel->getResource()->getAttribute($attributeCode);
            if ($productAttribute !== false) {
                if ($attrCounter > 1) {
                    $skuAttributes .= self::SEPARATOR;
                }
                $value = $productAttribute->getFrontend()->getValue($productModel);
                $skuAttributes .= $attributeCode . '=' . $value;
            }
        }
        return $skuAttributes;
    }

    /**
     * Tests to see if the stock values have been set
     *
     * @param array $productData
     *
     * @return bool
     */
    public function isStockSpecified(array $productData)
    {
        if (isset($productData[self::IS_IN_STOCK_COLUMN_HEADING]) && isset($productData[self::QTY_COLUMN_HEADING])) {
            return true;
        }
        return false;
    }

    /**
     * Set the stock values
     *
     * @param array $productData
     *
     * @return array
     */
    public function setStock(array $productData)
    {
        $newProductData = $productData;
        if (isset($productData[self::IS_IN_STOCK_COLUMN_HEADING]) &&
            $productData[self::IS_IN_STOCK_COLUMN_HEADING] == 1 &&
            isset($productData[self::QTY_COLUMN_HEADING]) == false) {
            $newProductData[self::QTY_COLUMN_HEADING] = 1;
        }
        return $newProductData;
    }

    /**
     * Replace the separator ','
     *
     * @param $data
     * @param $column
     *
     * @return mixed
     */
    private function replaceSeparator($data, $column)
    {
        if (in_array($column, $this->attrSeparator)) {
            return str_replace(',', self::SEPARATOR, $data);
        }
        return $data;
    }

    /**
     * Tidy up the value
     *
     * @param $value
     * @param $column
     *
     * @return string
     */
    private function clean($value, $column)
    {
        $value = $this->replaceSeparator($value, $column);
        return trim($value);
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
}
