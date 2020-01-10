<?php

namespace CtiDigital\Configurator\Api;

interface ComponentInterface
{
    /**
     * @param array $data
     * @return void
     */
    public function execute($data);

    /**
     * @return string
     */
    public function getAlias();

    /**
     * @return string
     */
    public function getDescription();
}
