<?php

namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Model\Component\Websites;

class WebsitesTest extends ComponentAbstractTestCase
{

    protected function componentSetUp()
    {
        $this->component = new Websites();
        $this->className = Websites::class;
    }
}