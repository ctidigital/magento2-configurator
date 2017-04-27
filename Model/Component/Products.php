<?php
namespace CtiDigital\Configurator\Model\Component;

use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\ObjectManagerInterface;
use CtiDigital\Configurator\Model\LoggingInterface;
use FireGento\FastSimpleImport\Model\ImporterFactory;
use CtiDigital\Configurator\Model\Exception\ComponentException;
use Magento\Framework\Http\ZendClientFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Class Products
 * @package CtiDigital\Configurator\Model\Component
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Products extends CsvComponentAbstract
{
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
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var ZendClientFactory
     */
    protected $httpClientFactory;

    public function __construct(
        LoggingInterface $log,
        ObjectManagerInterface $objectManager,
        ImporterFactory $importerFactory,
        ProductFactory $productFactory,
        ZendClientFactory $httpClientFactory,
        Filesystem $filesystem
    ) {
        parent::__construct($log, $objectManager);
        $this->productFactory= $productFactory;
        $this->importerFactory = $importerFactory;
        $this->httpClientFactory = $httpClientFactory;
        $this->filesystem = $filesystem;
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
        unset($data[0]);

        // Prepare the data
        $productsArray = array();

        foreach ($data as $product) {
            $productArray = array();
            foreach ($attributeKeys as $column => $code) {
                if (in_array($code, $this->imageAttributes)) {
                    $product[$column] = $this->getImage($product[$column]);
                }
                $productArray[$code] = $product[$column];
            }
            if ($this->isConfigurable($productArray)) {
                $variations = $this->constructConfigurableVariations($productArray);
                if (strlen($variations) > 0) {
                    $productArray['configurable_variations'] = $variations;
                }
                unset($productArray['associated_products']);
                unset($productArray['configurable_attributes']);
            }
            $productsArray[] = $productArray;
        }

        try {
            $import = $this->importerFactory->create();
            $import->processImport($productsArray);
            $this->log->logInfo($import->getLogTrace());
        } catch (\Exception $e) {
            $this->log->logError($import->getErrorMessages());
            $this->log->logError($import->getLogTrace());
        }
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

            if (is_array($products)) {
                $productsCount = count($products);
                $count = 0;
                foreach ($products as $sku) {
                    $productModel = $this->productFactory->create();
                    $id = $productModel->getIdBySku($sku);
                    $productModel->load($id);

                    if ($productModel->getId()) {
                        $configSkuAttributes = $this->constructAttributeData($attributes, $productModel);
                        $variations .= 'sku=' . $sku . ',' . $configSkuAttributes;
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
     * Checks if a value is a URL
     *
     * @param $url
     * @return bool|string
     */
    public function isValueURL($url)
    {
        return filter_var($url, FILTER_VALIDATE_URL);
    }

    /**
     * Download a file and return the response
     *
     * @param $value
     * @return string
     */
    public function downloadFile($value)
    {
        /**
         * @var \Magento\Framework\Http\ZendClient $client
         */
        $client = $this->httpClientFactory->create();
        $response = '';
        try {
            $response = $client
                ->setUri($value)
                ->request('GET')
                ->getBody();
        } catch (\Exception $e) {
            $this->log->logError($e->getMessage());
        }
        return $response;
    }

    /**
     * Get the file name from the URL
     *
     * @param $url
     * @return string
     */
    public function getFileName($url)
    {
        return basename($url);
    }

    /**
     * Saves the file. If the file exists, a number will be appended to the end of the file name
     *
     * @param $fileName
     * @param $value
     * @return Filesystem|string
     */
    public function saveFile($fileName, $value)
    {
        $name = pathinfo($fileName, PATHINFO_FILENAME);
        $ext = pathinfo($fileName, PATHINFO_EXTENSION);
        /**
         * @var Filesystem $file
         */
        $writeDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $importDirectory = $writeDirectory->getRelativePath('import');
        $counter = 0;
        do {
            $file = $name . '_' . $counter . '.' . $ext;
            $filePath = $writeDirectory->getRelativePath($importDirectory . DIRECTORY_SEPARATOR . $file);
            $counter++;
        } while ($writeDirectory->isExist($filePath));

        try {
            $writeDirectory->writeFile($filePath, $value);
        } catch (\Exception $e) {
            $this->log->logError($e->getMessage());
        }
        return $file;
    }

    /**
     * Downloads the image, saves, and returns the file name
     *
     * @param $value
     * @return Filesystem|string
     */
    public function getImage($value)
    {
        if ($this->isValueURL($value) === false) {
            return $value;
        }
        $file = $this->downloadFile($value);
        if (strlen($file) > 0) {
            $fileName = $this->getFileName($value);
            $fileContent = $this->saveFile($fileName, $file);
            return $fileContent;
        }
        return $value;
    }
}
