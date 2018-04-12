<?php
namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Component\AdminUsers;
use Magento\User\Model\UserFactory;
use Magento\Authorization\Model\RoleFactory;

class AdminUsersTest extends ComponentAbstractTestCase
{

    protected function componentSetUp()
    {

        $userFactory = $this->getMockBuilder(UserFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $rulesFactory = $this->getMockBuilder(RoleFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->component = new AdminUsers(
            $this->logInterface,
            $this->objectManager,
            $userFactory,
            $rulesFactory
        );
        $this->className = AdminUsers::class;
    }
}
