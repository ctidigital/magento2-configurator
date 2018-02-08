<?php

namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Component\Attributes;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Model\AttributeRepository;

class AttributesTest extends ComponentAbstractTestCase
{

    protected function componentSetUp()
    {
        $eavSetup = $this->getMock(EavSetup::class, [], [], '', false);
        $attributeRepository = $this->getMock(AttributeRepository::class, [], [], '', false);
        $this->component = new Attributes($this->logInterface, $this->objectManager, $eavSetup, $attributeRepository);
        $this->className = Attributes::class;
    }
}
