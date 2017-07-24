<?php
namespace CtiDigital\Configurator\Model\Component\Product;

use CtiDigital\Configurator\Model\LoggingInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;

class Image
{
    /**
     * @var LoggingInterface
     */
    protected $log;

    /**
     * @var \Magento\Framework\Http\ZendClientFactory
     */
    protected $httpClientFactory;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var \FireGento\FastSimpleImport\Helper\Config
     */
    protected $importerConfig;

    /**
     * Image constructor.
     *
     * @param LoggingInterface $log
     * @param Filesystem $filesystem
     * @param \FireGento\FastSimpleImport\Helper\Config $importerConfig
     * @param \Magento\Framework\Http\ZendClientFactory $httpClientFactory
     */
    public function __construct(
        LoggingInterface $log,
        Filesystem $filesystem,
        \FireGento\FastSimpleImport\Helper\Config $importerConfig,
        \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory
    ) {
        $this->log = $log;
        $this->filesystem = $filesystem;
        $this->importerConfig = $importerConfig;
        $this->httpClientFactory = $httpClientFactory;
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
         * @var \Magento\Framework\HTTP\ZendClient $client
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

        $writeDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $importDirectory = $this->getFileDirectory($writeDirectory);
        $counter = 0;
        do {
            if ($counter === 0) {
                $file = $fileName;
            } else {
                $file = $name . '_' . $counter . '.' . $ext;
            }
            $filePath = $writeDirectory->getRelativePath($importDirectory . DIRECTORY_SEPARATOR . $file);
            $counter++;
        } while ($writeDirectory->isExist($filePath));

        try {
            $writeDirectory->writeFile($filePath, $value);
            if ($this->isValidImage($writeDirectory->getAbsolutePath($filePath)) === false) {
                $this->log->logError(sprintf('The file %s is not valid and has been removed.', $filePath));
                $writeDirectory->delete($filePath);
                return '';
            }
        } catch (\Exception $e) {
            $this->log->logError($e->getMessage());
        }
        return $file;
    }

    private function isValidImage($file)
    {
        return exif_imagetype($file);
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
        if ($this->localFileExists($value)) {
            return $this->getFileName($value);
        }
        $this->log->logInfo(sprintf('Downloading image %s', $value));
        $file = $this->downloadFile($value);
        if (strlen($file) > 0) {
            $fileName = $this->getFileName($value);
            $fileContent = $this->saveFile($fileName, $file);
            return $fileContent;
        }
        return $value;
    }

    /**
     * Get the file directory from the configuration if set
     *
     * @param Filesystem\Directory\WriteInterface $file
     * @return string
     */
    public function getFileDirectory(\Magento\Framework\Filesystem\Directory\WriteInterface $file)
    {
        $configurationValue = $this->importerConfig->getImportFileDir();
        if (!empty($configurationValue)) {
            return $file->getRelativePath($configurationValue);
        }
        return $file->getRelativePath('import');
    }

    /**
     * Tests if the file exists locally
     *
     * @param $value
     *
     * @return bool
     */
    public function localFileExists($value)
    {
        $writeDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $importDirectory = $this->getFileDirectory($writeDirectory);
        $fileName= $this->getFileName($value);
        $filePath = $writeDirectory->getRelativePath($importDirectory . DIRECTORY_SEPARATOR . $fileName);
        if ($writeDirectory->isExist($filePath)) {
            return true;
        }
        return false;
    }
}
