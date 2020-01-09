<?php
namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Component\AdminRoles;
use Magento\Authorization\Model\RoleFactory;
use Magento\Authorization\Model\RulesFactory;

class AdminRolesTest extends ComponentAbstractTestCase
{

    protected function componentSetUp()
    {

        $roleFactory = $this->getMockBuilder(RoleFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $rulesFactory = $this->getMockBuilder(RulesFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->component = new AdminRoles(
            $this->logInterface,
            $this->objectManager,
            $this->json,
            $roleFactory,
            $rulesFactory
        );
        $this->className = AdminRoles::class;
    }
}
