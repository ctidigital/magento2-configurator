<?php

namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Api\ComponentInterface;
use CtiDigital\Configurator\Exception\ComponentException;
use CtiDigital\Configurator\Api\LoggerInterface;
use Magento\Framework\App\Filesystem\DirectoryList;

class Media implements ComponentInterface
{
    const FULL_ACCESS = 0777;

    protected $alias = 'media';
    protected $name = 'Media';
    protected $description = 'Component to download/maintain media.';

    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * @var LoggerInterface
     */
    private $log;

    public function __construct(
        DirectoryList $directoryList,
        LoggerInterface $log
    ) {
        $this->directoryList = $directoryList;
        $this->log = $log;
    }

    /**
     * @param $data
     */
    public function execute($data = null)
    {
        try {
            // Load root media path
            $mediaPath = $this->directoryList->getPath(DirectoryList::MEDIA);

            // Loop through top level nodes
            foreach ($data as $name => $childNode) {
                // Create a child folder or file item
                $this->createChildFolderFileItem($mediaPath, $name, $childNode);
            }
        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }
    }

    private function createChildFolderFileItem($currentPath, $name, $node, $nest = 0)
    {
        try {
            // Update the current path to the new path
            $newPath = $currentPath . DIRECTORY_SEPARATOR . $name;

            // Check if a folder exists and create if required
            $this->checkAndCreateFolder($newPath, $name, $nest);

            // If the node does not have a numeric index
            if (!is_numeric($name)) {
                $nest++;

                // Loop through the child node
                foreach ($node as $childName => $childNode) {
                    // Create a child folder
                    $this->createChildFolderFileItem($newPath, $childName, $childNode, $nest);
                }

                return;
            }

            if (!isset($node['name'])) {
                throw new ComponentException(sprintf('No name set for a child item in %s', $currentPath));
            }

            if (!isset($node['location'])) {
                throw new ComponentException(sprintf('No location set for a child item in %s', $currentPath));
            }

            $newPath = $currentPath . DIRECTORY_SEPARATOR . $node['name'];

            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            if (file_exists($newPath)) {
                $this->log->logComment(sprintf('File already exists: %s', $newPath), $nest);
                return;
            }

            // Download the file and place it in the price place
            $this->downloadAndSetFile($newPath, $node, $nest);
        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage(), $nest);
        }
    }

    /**
     * @param $newPath
     * @param $name
     * @param $nest
     */
    private function checkAndCreateFolder($newPath, $name, $nest)
    {
        // Check if the file/folder exists
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        if (!file_exists($newPath)) {
            // If the node does not have a numeric index
            if (!is_numeric($name)) {
                // Then it is a directory so create it
                // phpcs:ignore Magento2.Functions.DiscouragedFunction
                mkdir($newPath, $this::FULL_ACCESS, true);
                $this->log->logInfo(sprintf('Created new media directory %s', $name), $nest);
            }

            return;
        }

        // If the node does not have a numeric index
        if (!is_numeric($name)) {
            $this->log->logComment(sprintf('Directory Exists %s', $name), $nest);
        }
    }

    /**
     * @param $path
     * @param $node
     * @param $nest
     */
    private function downloadAndSetFile($path, $node, $nest)
    {
        $this->log->logInfo(sprintf('Downloading contents of file from %s', $node['location']), $nest);
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $fileContents = file_get_contents($node['location']);
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        file_put_contents($path, $fileContents);
        $this->log->logInfo(sprintf('Created new file: %s', $path), $nest);
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
