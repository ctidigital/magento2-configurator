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
                            $variations .= 'sku=' . $sku . ',' . $configSkuAttributes;
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
                    $skuAttributes .= ',';
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
        if (isset($productData[self::IS_IN_STOCK_COLUMN_HEADING]) || isset($productData[self::QTY_COLUMN_HEADING])) {
            return true;
        }
        return false;
    }

    private function setStock(array $productData)
    {
        $newProductData = $productData;
        $newProductData[self::QTY_COLUMN_HEADING] = 1;
        $newProductData[self::IS_IN_STOCK_COLUMN_HEADING] = 1;
        return $newProductData;
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
