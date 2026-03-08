<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Model;

use CtiDigital\Configurator\Api\ComponentListInterface;
use CtiDigital\Configurator\Api\ComponentRunnerInterface;
use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Api\MasterYamlReaderInterface;
use CtiDigital\Configurator\Api\SourceDataParserInterface;
use CtiDigital\Configurator\Exception\ComponentException;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;

/**
 * Orchestrates the configurator run: reads the master YAML and dispatches each
 * enabled component to the area-emulated runner.
 *
 * File parsing, master-YAML validation, and component execution logic are
 * delegated to the injected services; this class owns only the run-order and
 * Magento area-emulation concerns.
 */
class Processor
{
    protected string $environment;

    protected array $components = [];

    protected bool $ignoreMissingFiles = false;

    public function __construct(
        protected readonly ComponentListInterface $componentList,
        protected readonly State $state,
        protected readonly LoggerInterface $log,
        protected readonly MasterYamlReaderInterface $masterYamlReader,
        protected readonly SourceDataParserInterface $parser,
        protected readonly ComponentRunnerInterface $componentRunner
    ) {
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
     * Run all enabled components (or only the explicitly selected ones).
     */
    public function run(): void
    {
        if (empty($this->components)) {
            $this->runAllComponents();
            return;
        }

        $this->runIndividualComponents();
    }

    /**
     * Callback used by State::emulateAreaCode() — must remain public.
     *
     * Delegates all component-execution logic to ComponentRunnerInterface.
     */
    public function runComponent(string $componentAlias, array $componentConfig): void
    {
        $this->componentRunner->execute(
            $componentAlias,
            $componentConfig,
            $this->environment,
            $this->ignoreMissingFiles
        );
    }

    /**
     * Proxy to SourceDataParserInterface — preserved for backwards compatibility.
     */
    public function isSourceRemote(mixed $source): bool
    {
        return $this->parser->isSourceRemote($source);
    }

    /**
     * Proxy to SourceDataParserInterface — preserved for backwards compatibility.
     */
    public function getRemoteData(mixed $source): mixed
    {
        return $this->parser->getRemoteData($source);
    }

    private function runAllComponents(): void
    {
        try {
            $master = $this->masterYamlReader->read();

            foreach ($master as $componentAlias => $componentConfig) {
                if ($componentConfig['enabled'] === 0) {
                    continue;
                }
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

    private function runIndividualComponents(): void
    {
        try {
            $master = $this->masterYamlReader->read();

            foreach ($this->components as $componentAlias) {
                if (!isset($master[$componentAlias])) {
                    throw new ComponentException(
                        sprintf("No master yaml definition with the alias '%s' found", $componentAlias)
                    );
                }

                $this->state->emulateAreaCode(
                    Area::AREA_ADMINHTML,
                    [$this, 'runComponent'],
                    [$componentAlias, $master[$componentAlias]]
                );
            }
        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }
    }
}
