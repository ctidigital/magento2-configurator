<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Api;

use Magento\ImportExport\Model\Import\AbstractSource;

/**
 * Builds an in-memory import source from an array of rows.
 *
 * Magento core only ships a file-based CSV source; this factory lets the
 * configurator feed already-parsed data straight into the native importer.
 */
interface ImportAdapterFactoryInterface
{
    /**
     * @param array $data Expects ['data' => array<int, array<string, mixed>>, ...]
     */
    public function create(array $data = []): AbstractSource;
}
