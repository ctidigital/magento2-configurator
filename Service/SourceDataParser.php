<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Service;

use CtiDigital\Configurator\Api\SourceDataParserInterface;
use CtiDigital\Configurator\Exception\ComponentException;
use Exception;
use Symfony\Component\Yaml\Yaml;

/**
 * Parses a configurator data source (YAML, CSV, or JSON; local file or remote URL)
 * into a PHP value ready for a component to consume.
 *
 * Local file paths are resolved relative to the Magento base path (BP).
 */
class SourceDataParser implements SourceDataParserInterface
{
    private const SOURCE_YAML = 'yaml';
    private const SOURCE_CSV  = 'csv';
    private const SOURCE_JSON = 'json';

    /**
     * @inheritDoc
     */
    public function parse(mixed $source, ?string $sourceType): mixed
    {
        if ($this->canParseAndProcess($source) !== true) {
            return null;
        }

        $ext = ($sourceType !== null) ? $sourceType : $this->getExtension($source);

        if ($ext === self::SOURCE_YAML) {
            return $this->parseYamlData($this->getData($source));
        }
        if ($ext === self::SOURCE_CSV) {
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            return $this->parseCsvData($source);
        }
        if ($ext === self::SOURCE_JSON) {
            return $this->parseJsonData($this->getData($source));
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function isSourceRemote(mixed $source): bool
    {
        return filter_var($source, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * @inheritDoc
     */
    public function getRemoteData(mixed $source): mixed
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        return file_get_contents($source);
    }

    /**
     * Assert that the source can be read before attempting to parse it.
     *
     * @throws ComponentException When a local file does not exist.
     */
    private function canParseAndProcess(mixed $source): bool
    {
        $path = BP . '/' . $source;
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        if ($this->isSourceRemote($source) === false && !file_exists($path)) {
            throw new ComponentException(
                sprintf("Could not find file in path %s", $path)
            );
        }
        return true;
    }

    /**
     * Determine the file type (yaml / csv / json) for a given source.
     *
     * @throws ComponentException When the extension is not recognised.
     * @throws Exception
     */
    private function getExtension(mixed $source): string
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $extension = pathinfo((string)$source, PATHINFO_EXTENSION);

        if ($this->isSourceRemote($source)) {
            $extension = $this->getRemoteContentExtension($source);
        }

        $ext = strtolower((string)$extension);

        if ($ext === 'yaml') {
            return self::SOURCE_YAML;
        }
        if ($ext === 'csv') {
            return self::SOURCE_CSV;
        }
        if ($ext === 'json') {
            return self::SOURCE_JSON;
        }

        throw new ComponentException(
            sprintf('Source "%s" does not have a valid file extension.', $source)
        );
    }

    /**
     * Retrieve the raw content for a source (local file or remote URL).
     */
    private function getData(mixed $source): mixed
    {
        if ($this->isSourceRemote($source) === true) {
            return $this->getRemoteData($source);
        }
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        return file_get_contents(BP . '/' . $source);
    }

    /**
     * Resolve the MIME-type extension for a remote URL by inspecting response headers.
     *
     * @throws Exception
     */
    private function getRemoteContentExtension(mixed $source): mixed
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $headers = get_headers($source, 1);
        if ($headers === false) {
            return '';
        }
        $contentType = array_key_exists('Content-Type', $headers) ? $headers['Content-Type'] : '';

        $matches = [];
        preg_match('%^text/([a-z]+)%', (string)$contentType, $matches);
        return (count($matches) === 2) ? $matches[1] : null;
    }

    /**
     * Open a readable file handle for a local or remote source.
     *
     * @throws ComponentException When the handle cannot be opened.
     */
    private function getFileHandle(mixed $source): mixed
    {
        if ($this->isSourceRemote($source)) {
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $handle = fopen($source, 'r');
            if ($handle === false) {
                throw new ComponentException("Can't open CSV source for reading: {$source}");
            }
            return $handle;
        }

        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        return fopen(BP . '/' . $source, 'r');
    }

    /**
     * Parse a YAML string into a PHP value.
     */
    private function parseYamlData(mixed $source): mixed
    {
        return (new Yaml())->parse($source);
    }

    /**
     * Parse a CSV source into a two-dimensional array keyed by header column index.
     *
     * @throws ComponentException
     * @throws Exception
     * @return array<int, array<int, string>>
     */
    private function parseCsvData(mixed $source): array
    {
        $handle = $this->getFileHandle($source);

        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $headerRow = fgetcsv($handle, escape: '');
        $csvData   = [$headerRow];

        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        while (($csvLine = fgetcsv($handle, escape: '')) !== false) {
            $csvRow = [];
            foreach (array_keys($headerRow) as $key) {
                $csvRow[$key] = array_key_exists($key, $csvLine) ? $csvLine[$key] : '';
            }
            $csvData[] = $csvRow;
        }

        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        fclose($handle);
        return $csvData;
    }

    /**
     * Decode a JSON string into a PHP value.
     */
    private function parseJsonData(mixed $source): mixed
    {
        return json_decode((string)$source);
    }
}
