<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Model;

use CtiDigital\Configurator\Api\ComponentInterface;
use CtiDigital\Configurator\Api\ComponentListInterface;
use CtiDigital\Configurator\Api\FileComponentInterface;
use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Exception\ComponentException;
use Exception;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml;

/**
 * Class Processor - The overarching class that reads and processes the configurator files.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ShortVariable)
 */
class Processor
{
    private const SOURCE_YAML = 'yaml';
    private const SOURCE_CSV = 'csv';
    private const SOURCE_JSON = 'json';

    protected string $environment;

    protected array $components = [];

    protected ComponentListInterface $componentList;

    protected State $state;

    protected LoggerInterface $log;

    protected bool $ignoreMissingFiles = false;

    /**
     * Processor constructor.
     */
    public function __construct(
        ComponentListInterface $componentList,
        State $state,
        LoggerInterface $logging
    ) {
        $this->componentList = $componentList;
        $this->state = $state;
        $this->log = $logging;
    }

    /**
     * Return the logger instance.
     */
    public function getLogger(): LoggerInterface
    {
        return $this->log;
    }

    /**
     * Set whether to ignore missing source files.
     */
    public function setIgnoreMissingFiles(bool $setting): void
    {
        $this->ignoreMissingFiles = $setting;
    }

    /**
     * Return whether missing source files are ignored.
     */
    public function isIgnoreMissingFiles(): bool
    {
        return $this->ignoreMissingFiles;
    }

    /**
     * Add a component alias to the run list.
     *
     * @return $this
     */
    public function addComponent(string $componentName): static
    {
        $this->components[$componentName] = $componentName;
        return $this;
    }

    /**
     * Return the list of component aliases to run.
     */
    public function getComponents(): array
    {
        return $this->components;
    }

    /**
     * Set the environment name.
     *
     * @return $this
     */
    public function setEnvironment(string $environment): static
    {
        $this->environment = $environment;
        return $this;
    }

    /**
     * Return the environment name.
     */
    public function getEnvironment(): string
    {
        return $this->environment;
    }

    /**
     * Run the components individually.
     */
    public function run(): void
    {
        // If the components list is empty, then the user would want to run all components in the master.yaml
        if (empty($this->components)) {
            $this->runAllComponents();
            return;
        }

        $this->runIndividualComponents();
    }

    private function runIndividualComponents(): void
    {
        try {
            // Get the master yaml
            $master = $this->getMasterYaml();

            // Loop through the components
            foreach ($this->components as $componentAlias) {
                // Get the config for the component from the master yaml array
                if (!isset($master[$componentAlias])) {
                    throw new ComponentException(
                        sprintf("No master yaml definition with the alias '%s' found", $componentAlias)
                    );
                }

                $masterConfig = $master[$componentAlias];

                // Run that component
                $this->state->emulateAreaCode(
                    Area::AREA_ADMINHTML,
                    [$this, 'runComponent'],
                    [$componentAlias, $masterConfig]
                );
            }
        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }
    }

    private function runAllComponents(): void
    {
        try {
            // Get the master yaml
            $master = $this->getMasterYaml();

            // Loop through components and run them individually in the master.yaml order
            foreach ($master as $componentAlias => $componentConfig) {
                if ($componentConfig['enabled'] === 0) {
                    continue;
                }
                // Run the component in question
                $this->state->emulateAreaCode(
                    Area::AREA_ADMINHTML,
                    [$this, 'runComponent'],
                    [$componentAlias, $componentConfig]
                );
            }
        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }
    }

    /**
     * Run a single component with its configuration.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function runComponent(string $componentAlias, array $componentConfig): void
    {
        $this->log->logComment("");
        $this->log->logComment(str_pad("----------------------", (22 + strlen((string)$componentAlias)), "-"));
        $this->log->logComment(sprintf("| Loading component %s |", $componentAlias));
        $this->log->logComment(str_pad("----------------------", (22 + strlen((string)$componentAlias)), "-"));

        /* @var ComponentInterface $component */
        $component = $this->componentList->getComponent($componentAlias);

        $sourceType = (isset($componentConfig['type']) === true) ? $componentConfig['type'] : null;

        if (isset($componentConfig['sources'])) {
            foreach ($componentConfig['sources'] as $source) {
                try {
                    $sourceData = ($component instanceof FileComponentInterface) ?
                        $source :
                        $this->parseData($source, $sourceType);
                    $component->execute($sourceData);
                } catch (ComponentException $e) {
                    if ($this->isIgnoreMissingFiles() === true) {
                        $this->log->logInfo("Skipping file {$source} as it could not be found.");
                        continue;
                    }
                    throw $e;
                }
            }
        }

        // Check if there are environment specific nodes placed
        if (!isset($componentConfig['env'])) {
            // If not, continue to next component
            $this->log->logComment(
                sprintf("No environment node for '%s' component", $componentAlias)
            );
            return;
        }

        // Check if there is a node for this particular environment
        if (!isset($componentConfig['env'][$this->getEnvironment()])) {
            // If not, continue to next component
            $this->log->logComment(
                sprintf(
                    "No '%s' environment specific node for '%s' component",
                    $this->getEnvironment(),
                    $componentAlias
                )
            );
            return;
        }

        // Check if there are sources for the environment
        if (!isset($componentConfig['env'][$this->getEnvironment()]['sources'])) {
            // If not continue
            $this->log->logComment(
                sprintf(
                    "No '%s' environment specific sources for '%s' component",
                    $this->getEnvironment(),
                    $componentAlias
                )
            );
            return;
        }

        // If there are sources for the environment, process them
        foreach ((array)$componentConfig['env'][$this->getEnvironment()]['sources'] as $source) {
            try {
                $sourceType = (isset($componentConfig['type']) === true) ? $componentConfig['type'] : null;
                $sourceData = $this->parseData($source, $sourceType);
                $component->execute($sourceData);
            } catch (ComponentException $e) {
                if ($this->isIgnoreMissingFiles() === true) {
                    $this->log->logInfo("Skipping file {$source} as it could not be found.");
                    continue;
                }
                throw $e;
            }
        }
    }

    /**
     * Read and parse the master YAML file.
     */
    private function getMasterYaml(): array
    {
        // Read master yaml
        $masterPath = BP . '/app/etc/master.yaml';
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        if (!file_exists($masterPath)) {
            throw new ComponentException("Master YAML does not exist. Please create one in $masterPath");
        }
        $this->log->logComment(sprintf("Found Master YAML"));
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $yamlContents = file_get_contents($masterPath);
        $yaml = new Parser();
        $master = $yaml->parse($yamlContents);

        // Validate master yaml
        $this->validateMasterYaml($master);

        return $master;
    }

    /**
     * See if the component in master yaml exists.
     */
    private function isValidComponent(string $componentName): bool
    {
        if ($this->log->getLogLevel() > OutputInterface::VERBOSITY_NORMAL) {
            $this->log->logQuestion(sprintf("Does the %s component exist?", $componentName));
        }
        $component = $this->componentList->getComponent($componentName);

        if ($component instanceof ComponentInterface) {
            return true;
        }
        return false;
    }

    /**
     * Basic validation of master yaml requirements.
     *
     * @SuppressWarnings(PHPMD)
     */
    private function validateMasterYaml(array $master): void
    {
        try {
            foreach ($master as $componentAlias => $componentConfig) {
                // Check it has a enabled node
                if (!isset($componentConfig['enabled'])) {
                    throw new ComponentException(
                        sprintf('It appears %s does not have a "enabled" node. This is required.', $componentAlias)
                    );
                }
                // Check it has at least 1 data source
                $componentHasSource = false;

                if (isset($componentConfig['sources']) &&
                    is_array($componentConfig['sources']) &&
                    count($componentConfig['sources']) > 0 === true
                ) {
                    $componentHasSource = true;
                }

                if (isset($componentConfig['env']) === true) {
                    foreach ($componentConfig['env'] as $envData) {
                        if (isset($envData['sources']) &&
                            is_array($envData['sources']) &&
                            count($envData['sources']) > 0 === true
                        ) {
                            $componentHasSource = true;
                            break;
                        }
                    }
                }

                if ($componentHasSource === false) {
                    throw new ComponentException(
                        sprintf('It appears there are no data sources for the %s component.', $componentAlias)
                    );
                }

                // Check the component exist
                if (!$this->isValidComponent($componentAlias)) {
                    throw new ComponentException(
                        sprintf(
                            '%s not a valid component. Please verify using bin/magento component:list.',
                            $componentAlias
                        )
                    );
                }
            }
        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }
    }

    private function parseData(mixed $source, ?string $sourceType): mixed
    {
        if ($this->canParseAndProcess($source) === true) {
            $ext = ($sourceType !== null) ? $sourceType : $this->getExtension($source);

            if ($ext === self::SOURCE_YAML) {
                $sourceData = $this->getData($source);
                return $this->parseYamlData($sourceData);
            }
            if ($ext === self::SOURCE_CSV) {
                // Data is read directly from the source by parseCsvData()
                $this->log->logInfo(
                    sprintf('"%s" is being imported', $source)
                );
                return $this->parseCsvData($source);
            }
            if ($ext === self::SOURCE_JSON) {
                $sourceData = $this->getData($source);
                return $this->parseJsonData($sourceData);
            }
        }
        return null;
    }

    /**
     * This method is used to check whether the data from file or a third party
     * can be parsed and processed. (e.g. does a YAML file exist for it?)
     *
     * This will determine whether the component is enabled or disabled.
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
     * Return true if the source URL is remote.
     */
    public function isSourceRemote(mixed $source): bool
    {
        return filter_var($source, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Determine the file extension/type for a source path.
     *
     * @throws Exception
     */
    private function getExtension(mixed $source): string
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $extension = pathinfo((string)$source, PATHINFO_EXTENSION);

        // For remote files, use the mime type to determine the extension
        if ($this->isSourceRemote($source)) {
            $extension = $this->getRemoteContentExtension($source);
        }

        if (strtolower((string)$extension) === 'yaml') {
            return self::SOURCE_YAML;
        }
        if (strtolower((string)$extension) === 'csv') {
            return self::SOURCE_CSV;
        }
        if (strtolower((string)$extension) === 'json') {
            return self::SOURCE_JSON;
        }
        throw new ComponentException(sprintf('Source "%s" does not have a valid file extension.', $source));
    }

    /**
     * Retrieve the raw content for a source (local or remote).
     *
     * @throws Exception
     */
    private function getData(mixed $source): mixed
    {
        return ($this->isSourceRemote($source) === true) ?
            $this->getRemoteData($source) :
            file_get_contents(BP . '/' . $source); // phpcs:ignore Magento2.Functions.DiscouragedFunction
    }

    /**
     * Resolve the content-type extension for a remote URL.
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

        // Parse the 'extension' from the content type
        $matches = [];
        preg_match('%^text/([a-z]+)%', (string)$contentType, $matches);
        return (count($matches) == 2) ? $matches[1] : null;
    }

    /**
     * Fetch the raw content of a remote source.
     *
     * @throws Exception
     */
    public function getRemoteData(mixed $source): mixed
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        return file_get_contents($source);
    }

    /**
     * Parse a YAML string into a PHP value.
     */
    private function parseYamlData(mixed $source): mixed
    {
        return (new Yaml())->parse($source);
    }

    /**
     * Open a file handle for reading (local or remote).
     */
    private function getFileHandle(mixed $source): mixed
    {
        // Get a handle to the source data, whether it's remote or local
        if ($this->isSourceRemote($source)) {
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $handle = fopen($source, 'r');
            if ($handle === false) {
                throw new ComponentException("Can't open CSV source for reading: {$source}");
            }
            return $handle;
        }

        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        return fopen($source, 'r');
    }

    /**
     * Parse a CSV source into a two-dimensional array.
     *
     * @throws Exception
     */
    private function parseCsvData(mixed $source): array
    {
        $handle = $this->getFileHandle($source);

        // Read the header row
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $headerRow = fgetcsv($handle, escape: '');
        $csvData = [$headerRow];

        // Read all other rows and build up an array, with row headers as keys
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        while (($csvLine = fgetcsv($handle, escape: '')) !== false) {
            $csvRow = [];

            foreach (array_keys($headerRow) as $key) {
                $csvRow[$key] = (array_key_exists($key, $csvLine) === true) ? $csvLine[$key] : '';
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
