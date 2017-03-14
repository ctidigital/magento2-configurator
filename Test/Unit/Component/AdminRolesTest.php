<?php
namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Model\Component\AdminRoles;
use Magento\Authorization\Model\RoleFactory;
use Magento\Authorization\Model\RulesFactory;

class AdminRolesTest extends ComponentAbstractTestCase
{

    protected function componentSetUp()
    {

        $roleFactory = $this->getMock(RulesFactory::class);
        $rulesFactory = $this->getMock(RoleFactory::class);

        $this->component = new AdminRoles(
            $this->logInterface,
            $this->objectManager,
            $roleFactory,
            $rulesFactory
        );
        $this->className = AdminRoles::class;
    }
}
