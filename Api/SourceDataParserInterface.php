<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Api;

/**
 * Parses a configurator data source (YAML, CSV, or JSON; local or remote URL)
 * into a PHP value ready for a component to consume.
 */
interface SourceDataParserInterface
{
    /**
     * Parse a source file or URL into a PHP value.
     *
     * @param  mixed       $source     File path (relative to BP) or remote URL.
     * @param  string|null $sourceType Force a parser ('yaml', 'csv', 'json'). Auto-detected when null.
     * @return mixed       Parsed data, or null if the source cannot be processed.
     */
    public function parse(mixed $source, ?string $sourceType): mixed;

    /**
     * Return true if $source is a remote URL rather than a local path.
     */
    public function isSourceRemote(mixed $source): bool;

    /**
     * Fetch the raw content of a remote URL.
     */
    public function getRemoteData(mixed $source): mixed;
}
