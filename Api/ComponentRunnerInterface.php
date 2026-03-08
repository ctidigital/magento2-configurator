<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Api;

use CtiDigital\Configurator\Exception\ComponentException;

/**
 * Executes a single configurator component against its master-YAML configuration.
 */
interface ComponentRunnerInterface
{
    /**
     * Run a component with its configuration entry from master.yaml.
     *
     * @param  string $componentAlias    The registered alias of the component to run.
     * @param  array  $componentConfig   The component's full master-YAML config block.
     * @param  string $environment       The active deployment environment name.
     * @param  bool   $ignoreMissingFiles When true, missing source files are logged and skipped
     *                                    rather than causing a fatal error.
     * @throws ComponentException When a source file is missing and $ignoreMissingFiles is false.
     */
    public function execute(
        string $componentAlias,
        array $componentConfig,
        string $environment,
        bool $ignoreMissingFiles
    ): void;
}
