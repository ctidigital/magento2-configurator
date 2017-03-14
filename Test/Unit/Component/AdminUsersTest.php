<?php
namespace CtiDigital\Configurator\Test\Unit\Component;
use CtiDigital\Configurator\Model\Component\AdminUsers;
use Magento\User\Model\UserFactory;
use Magento\Authorization\Model\RoleFactory;
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
