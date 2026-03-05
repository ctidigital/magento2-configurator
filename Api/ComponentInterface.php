<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Api;

interface ComponentInterface
{
    /**
     * Execute the component with the given data.
     */
    public function execute(mixed $data = null): void;

    /**
     * Return the component alias.
     */
    public function getAlias(): string;

    /**
     * Return the component description.
     */
    public function getDescription(): string;
}
