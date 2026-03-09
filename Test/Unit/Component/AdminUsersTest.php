<?php

declare(strict_types=1);

namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Component\AdminUsers;
use Magento\Authorization\Model\ResourceModel\Role\Collection as RoleCollection;
use Magento\Authorization\Model\Role;
use Magento\Authorization\Model\RoleFactory;
use Magento\User\Model\ResourceModel\User\Collection as UserCollection;
use Magento\User\Model\User;
use Magento\User\Model\UserFactory;
use PHPUnit\Framework\TestCase;

class AdminUsersTest extends TestCase
{
    private AdminUsers $adminUsers;

    /** @var UserFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $userFactory;

    /** @var RoleFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $roleFactory;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $log;

    /** Valid user data used across multiple tests */
    private array $validUserData = [
        'username'   => 'testuser',
        'firstname'  => 'Test',
        'secondname' => 'User',
        'email'      => 'test@example.com',
        'password'   => 'Password1!',
    ];

    protected function setUp(): void
    {
        $this->userFactory = $this->getMockBuilder(UserFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $this->roleFactory = $this->getMockBuilder(RoleFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $this->log = $this->getMockBuilder(LoggerInterface::class)
            ->getMock();

        $this->adminUsers = new AdminUsers(
            $this->userFactory,
            $this->roleFactory,
            $this->log
        );
    }

    private function buildRoleMockWithId(mixed $id): Role
    {
        $roleCollectionMock = $this->getMockBuilder(RoleCollection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addFieldToFilter', 'getFirstItem'])
            ->getMock();

        $firstItemMock = $this->getMockBuilder(Role::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->getMock();
        $firstItemMock->method('getId')->willReturn($id);

        $roleCollectionMock->method('addFieldToFilter')->willReturnSelf();
        $roleCollectionMock->method('getFirstItem')->willReturn($firstItemMock);

        $roleMock = $this->getMockBuilder(Role::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCollection'])
            ->getMock();
        $roleMock->method('getCollection')->willReturn($roleCollectionMock);

        return $roleMock;
    }

    public function testGetAliasReturnsAdminusers(): void
    {
        $this->assertSame('adminusers', $this->adminUsers->getAlias());
    }

    public function testExecuteLogsErrorWhenRoleNotFound(): void
    {
        $this->roleFactory->method('create')->willReturn($this->buildRoleMockWithId(null));

        $this->userFactory->expects($this->never())->method('create');
        $this->log->expects($this->once())->method('logError')
            ->with($this->stringContains('does not exist'));

        $this->adminUsers->execute([
            'adminusers' => [
                ['rolename' => 'NonExistentRole', 'users' => []],
            ],
        ]);
    }

    public function testExecuteSkipsExistingUserByEmail(): void
    {
        $this->roleFactory->method('create')->willReturn($this->buildRoleMockWithId(3));

        $userCollectionMock = $this->getMockBuilder(UserCollection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addFieldToFilter', 'getSize'])
            ->getMock();
        $userCollectionMock->method('addFieldToFilter')->willReturnSelf();
        $userCollectionMock->method('getSize')->willReturn(1);

        $userMock = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCollection', 'save'])
            ->getMock();
        $userMock->method('getCollection')->willReturn($userCollectionMock);
        $userMock->expects($this->never())->method('save');

        $this->userFactory->method('create')->willReturn($userMock);
        $this->log->expects($this->once())->method('logComment');

        $this->adminUsers->execute([
            'adminusers' => [
                ['rolename' => 'Admin', 'users' => [$this->validUserData]],
            ],
        ]);
    }

    public function testExecuteCreatesUserWhenNotExists(): void
    {
        $this->roleFactory->method('create')->willReturn($this->buildRoleMockWithId(3));

        $userCollectionMock = $this->getMockBuilder(UserCollection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addFieldToFilter', 'getSize'])
            ->getMock();
        $userCollectionMock->method('addFieldToFilter')->willReturnSelf();
        $userCollectionMock->method('getSize')->willReturn(0);

        $userMock = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCollection', 'validate', 'save'])
            ->getMock();
        $userMock->method('getCollection')->willReturn($userCollectionMock);
        $userMock->method('validate')->willReturn(true);
        $userMock->expects($this->once())->method('save');

        $this->userFactory->method('create')->willReturn($userMock);

        $this->adminUsers->execute([
            'adminusers' => [
                ['rolename' => 'Admin', 'users' => [$this->validUserData]],
            ],
        ]);
    }

    public function testExecuteDoesNotSaveWhenValidateFails(): void
    {
        $this->roleFactory->method('create')->willReturn($this->buildRoleMockWithId(3));

        $userCollectionMock = $this->getMockBuilder(UserCollection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addFieldToFilter', 'getSize'])
            ->getMock();
        $userCollectionMock->method('addFieldToFilter')->willReturnSelf();
        $userCollectionMock->method('getSize')->willReturn(0);

        $userMock = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCollection', 'validate', 'save'])
            ->getMock();
        $userMock->method('getCollection')->willReturn($userCollectionMock);
        $userMock->method('validate')->willReturn(false);
        $userMock->expects($this->never())->method('save');

        $this->userFactory->method('create')->willReturn($userMock);

        $this->adminUsers->execute([
            'adminusers' => [
                ['rolename' => 'Admin', 'users' => [$this->validUserData]],
            ],
        ]);
    }
}
