<?php

namespace CtiDigital\Configurator\Api;

interface ComponentInterface
{
    /**
     * @param array $data
     * @return void
     */
    public function execute($data);
}
