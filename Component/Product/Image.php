<?php
namespace CtiDigital\Configurator\Component\Product;

use CtiDigital\Configurator\Api\LoggerInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use FireGento\FastSimpleImport\Model\Config;
use GuzzleHttp\Client;
use GuzzleHttp\ClientFactory;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Webapi\Rest\Request;

class Image
{
    /**
     * @var LoggerInterface
     */
    protected $log;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var Config
     */
    protected $importerConfig;

    /**
     * @var string
     */
    private $separator = ';';

    /**
     * @var ClientFactory
     */
    private ClientFactory $clientFactory;

    /**
     * @param Filesystem $filesystem
     * @param Config $importerConfig
     * @param ClientFactory $clientFactory
     * @param LoggerInterface $log
     */
    public function __construct(
        Filesystem $filesystem,
        Config $importerConfig,
        ClientFactory $clientFactory,
        LoggerInterface $log
    ) {
        $this->filesystem = $filesystem;
        $this->importerConfig = $importerConfig;
        $this->clientFactory = $clientFactory;
        $this->log = $log;
    }

    /**
     * @param $separator
     */
    public function setSeparator($separator)
    {
        $this->separator = $separator;
    }

    /**
     * @return string
     */
    public function getSeparator()
    {
        return $this->separator;
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
         * @var Client $client
         */
        $client = $this->clientFactory->create();

        try {
            $response = $client->request(Request::HTTP_METHOD_GET, $value)->getBody();
        } catch (GuzzleException $e) {
            $response = '';
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
        if (preg_match('/http:\/\/placehold\.it\/(.*)\/jpg$/', $url, $match)) {
            $imageName = sprintf('%s.jpg', $match[1]);
        } else {
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $imageName = basename((string) $url);
            // Remove any URL entities
            $imageName = urldecode($imageName);
            // Replace spaces with -
            $imageName = preg_replace('/\s+/', '-', $imageName);
        }

        return $imageName;
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
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $name = pathinfo((string) $fileName, PATHINFO_FILENAME);
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $ext = pathinfo((string) $fileName, PATHINFO_EXTENSION);

        $writeDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $importDirectory = $this->getFileDirectory($writeDirectory);
        $counter = 0;
        do {
            $file = $fileName;
            if ($counter > 0) {
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
        $validImages = [];
        $images = explode(',', (string) $value);
        foreach ($images as $image) {
            if ($this->isValueURL($image) === false) {
                $validImages[] = $image;
                continue;
            }
            if ($this->localFileExists($image)) {
                $validImages[] = $this->getFileName($image);
                continue;
            }
            $this->log->logInfo(sprintf('Downloading image %s', $image));
            $file = $this->downloadFile($image);
            if (strlen($file) > 0) {
                $fileName = $this->getFileName($image);
                $fileContent = $this->saveFile($fileName, $file);
                if ($fileContent !== '') {
                    $validImages[] = $fileContent;
                }
            }
        }
        return implode($this->getSeparator(), $validImages);
    }

    /**
     * Get the file directory from the configuration if set
     *
     * @param Filesystem\Directory\WriteInterface $file
     * @return string
     */
    public function getFileDirectory(\Magento\Framework\Filesystem\Directory\WriteInterface $file)
    {
        try {
            $configurationValue = $this->importerConfig->getImportFileDir();
             return $file->getRelativePath($configurationValue);
        } catch (\TypeError $e) {
            return $file->getRelativePath('import');
        }
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
