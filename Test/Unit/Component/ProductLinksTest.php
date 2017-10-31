<?php

namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Component\ProductLinks;

class ProductLinksTest extends ComponentAbstractTestCase
{

    protected function componentSetUp()
    {
        $this->component = $this->testObjectManager->getObject('CtiDigital\Configurator\Component\ProductLinks');
        $this->className = ProductLinks::class;
    }
}
