<?php

namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Model\Component\Widgets;

class WidgetsTest extends ComponentAbstractTestCase
{

    protected function componentSetUp()
    {
        $this->component = new Widgets($this->logInterface, $this->objectManager);
        $this->className = Widgets::class;
    }
}
