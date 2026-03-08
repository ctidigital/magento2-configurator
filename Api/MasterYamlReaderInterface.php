<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Api;

use CtiDigital\Configurator\Exception\ComponentException;

/**
 * Reads and validates the master YAML configuration file (app/etc/master.yaml).
 */
interface MasterYamlReaderInterface
{
    /**
     * Read, parse, and validate the master YAML file.
     *
     * @return array<string, mixed> Keyed by component alias.
     * @throws ComponentException   When the file is missing, malformed, or a component is invalid.
     */
    public function read(): array;
}
