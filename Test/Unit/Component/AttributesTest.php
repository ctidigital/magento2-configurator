<?php

namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Model\Component\Attributes;
use Magento\Eav\Setup\EavSetupFactory;

class AttributesTest extends ComponentAbstractTestCase
{

    protected function componentSetUp()
    {
        $eavSetupFactory = $this->getMock(EavSetupFactory::class);
        $this->component = new Attributes($this->logInterface, $this->objectManager, $eavSetupFactory);
        $this->className = Attributes::class;
    }
}
