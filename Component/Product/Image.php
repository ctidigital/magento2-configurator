<?php
declare(strict_types=1);

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
    protected LoggerInterface $log;

    /**
     * @var Filesystem
     */
    protected Filesystem $filesystem;

    /**
     * @var Config
     */
    protected Config $importerConfig;

    private string $separator = ';';

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

    public function setSeparator(string $separator): void
    {
        $this->separator = $separator;
    }

    public function getSeparator(): string
    {
        return $this->separator;
    }

    /**
     * Checks if a value is a URL.
     */
    public function isValueURL(mixed $url): bool|string
    {
        return filter_var($url, FILTER_VALIDATE_URL);
    }

    /**
     * Download a file and return the response.
     */
    public function downloadFile(mixed $value): mixed
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
     * Get the file name from the URL.
     */
    public function getFileName(mixed $url): string
    {
        if (preg_match('/http:\/\/placehold\.it\/(.*)\/jpg$/', $url, $match)) {
            return sprintf('%s.jpg', $match[1]);
        }

        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $imageName = basename((string) $url);
        // Remove any URL entities
        $imageName = urldecode($imageName);
        // Replace spaces with -
        return preg_replace('/\s+/', '-', $imageName);
    }

    /**
     * Saves the file. If the file exists, a number will be appended to the end of the file name.
     *
     * @return Filesystem|string
     */
    public function saveFile(mixed $fileName, mixed $value): mixed
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

    private function isValidImage(mixed $file): mixed
    {
        return exif_imagetype($file);
    }

    /**
     * Downloads the image, saves, and returns the file name.
     *
     * @return Filesystem|string
     */
    public function getImage(mixed $value): mixed
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
     * Get the file directory from the configuration if set.
     */
    public function getFileDirectory(\Magento\Framework\Filesystem\Directory\WriteInterface $file): string
    {
        try {
            $configurationValue = $this->importerConfig->getImportFileDir();
             return $file->getRelativePath($configurationValue);
        } catch (\TypeError $e) {
            return $file->getRelativePath('import');
        }
    }

    /**
     * Tests if the file exists locally.
     */
    public function localFileExists(mixed $value): bool
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
