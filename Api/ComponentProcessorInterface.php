<?php
declare(strict_types=1);

/**
 * @package  CtiDigital\Configurator
 * @author Bartosz Herba <b.herba@ctidigital.com>
 * @copyright 2017 CtiDigital
 */

namespace CtiDigital\Configurator\Api;

/**
 * Interface ComponentProcessorInterface
 */
interface ComponentProcessorInterface
{
    /**
     * Set the data to process.
     *
     * @return $this
     */
    public function setData(array $data): static;

    /**
     * Set the component configuration.
     *
     * @return $this
     */
    public function setConfig(array $config): static;

    /**
     * Configure rules.
     */
    public function process(): void;
}
