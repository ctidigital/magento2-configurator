<?php

namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Model\Component\Config;

class ConfigTest extends ComponentAbstractTestCase
{

    protected function componentSetUp()
    {
        $this->component = new Config($this->logInterface, $this->objectManager);
        $this->className = Config::class;
    }
}
