<?php

namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Component\AttributeSets;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Api\AttributeSetRepositoryInterface;

/**
 * Class AttributeSetsTest
 * @package CtiDigital\Configurator\Test\Unit\Component
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class AttributeSetsTest extends ComponentAbstractTestCase
{

    protected function componentSetUp()
    {
        $eavSetup = $this->getMockBuilder(EavSetup::class)
            ->disableOriginalConstructor()
            ->getMock();
        $attributeSetsRepositoryInterface = $this->getMockBuilder(AttributeSetRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->component = new AttributeSets(
            $this->logInterface,
            $this->objectManager,
            $eavSetup,
            $attributeSetsRepositoryInterface
        );

        $this->className = AttributeSets::class;
    }
}
