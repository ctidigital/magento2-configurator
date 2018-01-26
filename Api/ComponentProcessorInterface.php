<?php
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
     * @param array $data
     *
     * @return $this
     */
    public function setData(array $data);

    /**
     * @param array $config
     *
     * @return $this
     */
    public function setConfig(array $config);

    /**
     * Configure rules
     *
     * @return void
     */
    public function process();
}
