<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Model\Import\Source;

use Magento\ImportExport\Model\Import\AbstractSource;

/**
 * In-memory import source backed by a plain PHP array.
 *
 * Native Magento only provides a file-based CSV source; this adapter lets the
 * configurator validate and import data it has already parsed in memory.
 */
class ArrayAdapter extends AbstractSource
{
    private int $position = 0;

    /**
     * @param array<int, array<string, mixed>> $data
     */
    public function __construct(
        protected array $data
    ) {
        parent::__construct(array_keys($this->current()));
    }

    /**
     * Move to the given position, asserting it exists.
     *
     * @param int $position
     * @throws \OutOfBoundsException
     */
    public function seek($position): void
    {
        $this->position = $position;

        if (!$this->valid()) {
            throw new \OutOfBoundsException("Invalid seek position ($position)");
        }
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function current(): array
    {
        return $this->data[$this->position];
    }

    public function key(): int
    {
        return $this->position;
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function valid(): bool
    {
        return isset($this->data[$this->position]);
    }

    /**
     * @return array<string, string>
     */
    public function getColNames(): array
    {
        $colNames = [];
        foreach ($this->data as $row) {
            foreach (array_keys($row) as $key) {
                if (!is_numeric($key) && !isset($colNames[$key])) {
                    $colNames[$key] = $key;
                }
            }
        }
        return $colNames;
    }

    public function setValue(string $key, mixed $value): void
    {
        if (!$this->valid()) {
            return;
        }

        $this->data[$this->position][$key] = $value;
    }

    public function unsetValue(string $key): void
    {
        if (!$this->valid()) {
            return;
        }

        unset($this->data[$this->position][$key]);
    }

    /**
     * Render the next row.
     *
     * @return array|false
     */
    protected function _getNextRow()
    {
        $this->next();
        return $this->current();
    }
}
