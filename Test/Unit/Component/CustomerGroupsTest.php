<?php
namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Model\Component\CustomerGroups;
use Magento\Customer\Model\GroupFactory;
use Magento\Tax\Model\ClassModelFactory;

class CustomerGroupsTest extends ComponentAbstractTestCase
{

    protected function componentSetUp()
    {
        $groupFactory = $this->getMock(GroupFactory::class);
        $classModelFactory = $this->getMock(ClassModelFactory::class);

        $this->component = new CustomerGroups(
            $this->logInterface,
            $this->objectManager,
            $groupFactory,
            $classModelFactory
        );
        $this->className = CustomerGroups::class;
    }
}
