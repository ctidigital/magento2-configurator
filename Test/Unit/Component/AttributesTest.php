<?php

namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Component\Attributes;
use Magento\Eav\Setup\EavSetup;
use Magento\Catalog\Model\Product\Attribute\Repository as ProductAttributeRepository;

class AttributesTest extends ComponentAbstractTestCase
{

    protected function componentSetUp()
    {
        $eavSetup = $this->getMockBuilder(EavSetup::class)
            ->disableOriginalConstructor()
            ->getMock();
        $attributeRepository = $this->getMockBuilder(ProductAttributeRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->component = new Attributes($this->logInterface, $this->objectManager, $eavSetup, $attributeRepository);
        $this->className = Attributes::class;
    }
}
