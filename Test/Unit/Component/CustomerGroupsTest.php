<?php
namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Component\CustomerGroups;
use Magento\Customer\Model\GroupFactory;
use Magento\Tax\Model\ClassModelFactory;

class CustomerGroupsTest extends ComponentAbstractTestCase
{

    protected function componentSetUp()
    {
        $groupFactory = $this->getMockBuilder(GroupFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $classModelFactory = $this->getMockBuilder(ClassModelFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->component = new CustomerGroups(
            $this->logInterface,
            $this->objectManager,
            $groupFactory,
            $classModelFactory
        );
        $this->className = CustomerGroups::class;
    }
}
