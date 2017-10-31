<?php

namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Component\Websites;

class WebsitesTest extends ComponentAbstractTestCase
{

    protected function componentSetUp()
    {
        $this->component = $this->testObjectManager->getObject('CtiDigital\Configurator\Component\Websites');
        $this->className = Websites::class;
    }
}
