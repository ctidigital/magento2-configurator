<?php

namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Model\Component\ProductLinks;

class ProductLinksTest extends ComponentAbstractTestCase
{

    protected function componentSetUp()
    {
        $this->component = $this->testObjectManager->getObject('CtiDigital\Configurator\Model\Component\ProductLinks');
        $this->className = ProductLinks::class;
    }
}
