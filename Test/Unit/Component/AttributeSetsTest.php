<?php

namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Model\Component\AttributeSets;
use Magento\Eav\Setup\EavSetupFactory;
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
        $eavSetupFactory = $this->getMock(EavSetupFactory::class);
        $attributeSetsRepositoryInterface = $this->getMock(AttributeSetRepositoryInterface::class);

        $this->component = new AttributeSets(
            $this->logInterface,
            $this->objectManager,
            $eavSetupFactory,
            $attributeSetsRepositoryInterface
        );

        $this->className = AttributeSets::class;
    }
}
