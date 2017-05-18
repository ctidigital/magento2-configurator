<?php
namespace CtiDigital\Configurator\Test\Unit\Component;

use Magento\User\Model\UserFactory;
use Magento\Authorization\Model\RoleFactory;
use CtiDigital\Configurator\Model\Component\AdminUsers;

class AdminUsersTest extends ComponentAbstractTestCase
{

    protected function componentSetUp()
    {

        $userFactory = $this->getMock(UserFactory::class);
        $rulesFactory = $this->getMock(RoleFactory::class);

        $this->component = new AdminUsers(
            $this->logInterface,
            $this->objectManager,
            $userFactory,
            $rulesFactory
        );
        $this->className = AdminUsers::class;
    }
}
