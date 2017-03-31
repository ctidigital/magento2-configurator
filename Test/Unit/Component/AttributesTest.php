<?php

namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Model\Component\Attributes;
use Magento\Eav\Setup\EavSetup;
use Magento\Catalog\Model\Product\Attribute\Repository as ProductAttributeRepository;

class AttributesTest extends ComponentAbstractTestCase
{

    protected function componentSetUp()
    {
        $eavSetup = $this->getMock(EavSetup::class, [], [], '', false);
        $productAttributeRepository = $this->getMock(ProductAttributeRepository::class, [], [], '', false);
        $this->component = new Attributes(
            $this->logInterface,
            $this->objectManager,
            $eavSetup,
            $productAttributeRepository
        );
        $this->className = Attributes::class;
    }
}
