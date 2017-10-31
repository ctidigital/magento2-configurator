<?php
namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Component\AdminRoles;
use Magento\Authorization\Model\RoleFactory;
use Magento\Authorization\Model\RulesFactory;

class AdminRolesTest extends ComponentAbstractTestCase
{

    protected function componentSetUp()
    {

        $roleFactory = $this->getMock(RoleFactory::class);
        $rulesFactory = $this->getMock(RulesFactory::class);

        $this->component = new AdminRoles(
            $this->logInterface,
            $this->objectManager,
            $roleFactory,
            $rulesFactory
        );
        $this->className = AdminRoles::class;
    }
}
