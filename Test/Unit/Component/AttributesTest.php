<?php

namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Component\Attributes;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Setup\EavSetup;

class AttributesTest extends ComponentAbstractTestCase
{

    protected function componentSetUp()
    {
        $eavSetup = $this->getMockBuilder(EavSetup::class)
            ->disableOriginalConstructor()
            ->getMock();
        $attributeRepository = $this->getMockBuilder(AttributeRepositoryInterface::class)->getMock();
        $this->component = new Attributes(
            $this->logInterface,
            $this->objectManager,
            $this->json,
            $eavSetup,
            $attributeRepository
        );
        $this->className = Attributes::class;
    }
}
