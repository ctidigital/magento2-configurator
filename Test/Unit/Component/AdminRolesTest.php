<?php

declare(strict_types=1);

namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Component\AdminRoles;
use Magento\Authorization\Model\ResourceModel\Role\Collection as RoleCollection;
use Magento\Authorization\Model\Role;
use Magento\Authorization\Model\RoleFactory;
use Magento\Authorization\Model\Rules;
use Magento\Authorization\Model\RulesFactory;
use PHPUnit\Framework\TestCase;

class AdminRolesTest extends TestCase
{
    private AdminRoles $adminRoles;

    /** @var RoleFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $roleFactory;

    /** @var RulesFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $rulesFactory;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $log;

    protected function setUp(): void
    {
        $this->roleFactory = $this->getMockBuilder(RoleFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $this->rulesFactory = $this->getMockBuilder(RulesFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $this->log = $this->getMockBuilder(LoggerInterface::class)
            ->getMock();

        $this->adminRoles = new AdminRoles(
            $this->roleFactory,
            $this->rulesFactory,
            $this->log
        );
    }

    public function testGetAliasReturnsAdminroles(): void
    {
        $this->assertSame('adminroles', $this->adminRoles->getAlias());
    }

    public function testExecuteIsNoOpWhenAdminrolesKeyAbsent(): void
    {
        $this->roleFactory->expects($this->never())->method('create');

        $this->adminRoles->execute([]);
    }

    public function testExecuteIsNoOpWhenNameKeyMissing(): void
    {
        $this->roleFactory->expects($this->never())->method('create');

        $this->adminRoles->execute(['adminroles' => [['resources' => []]]]);
    }

    public function testExecuteCreatesNewRoleAndSetsResources(): void
    {
        $collectionMock = $this->getMockBuilder(RoleCollection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addFieldToFilter', 'getSize', 'getFirstItem'])
            ->getMock();
        $collectionMock->method('addFieldToFilter')->willReturnSelf();
        $collectionMock->method('getSize')->willReturn(0);

        $roleMock = $this->getMockBuilder(Role::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCollection', 'getId', 'save'])
            ->getMock();
        $roleMock->method('getCollection')->willReturn($collectionMock);
        $roleMock->expects($this->once())->method('save');

        $rulesMock = $this->getMockBuilder(Rules::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['saveRel'])
            ->getMock();
        $rulesMock->expects($this->once())->method('saveRel');

        $this->roleFactory->method('create')->willReturn($roleMock);
        $this->rulesFactory->method('create')->willReturn($rulesMock);

        $this->adminRoles->execute([
            'adminroles' => [
                ['name' => 'Test Role', 'resources' => ['Magento_Backend::all']],
            ],
        ]);
    }

    public function testExecuteSkipsCreationButSetsResourcesWhenRoleExists(): void
    {
        $existingRoleMock = $this->getMockBuilder(Role::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->getMock();
        $existingRoleMock->method('getId')->willReturn(5);

        $collectionMock = $this->getMockBuilder(RoleCollection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addFieldToFilter', 'getSize', 'getFirstItem'])
            ->getMock();
        $collectionMock->method('addFieldToFilter')->willReturnSelf();
        $collectionMock->method('getSize')->willReturn(1);
        $collectionMock->method('getFirstItem')->willReturn($existingRoleMock);

        $roleMock = $this->getMockBuilder(Role::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCollection', 'save'])
            ->getMock();
        $roleMock->method('getCollection')->willReturn($collectionMock);
        $roleMock->expects($this->never())->method('save');

        $rulesMock = $this->getMockBuilder(Rules::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['saveRel'])
            ->getMock();
        $rulesMock->expects($this->once())->method('saveRel');

        $this->roleFactory->method('create')->willReturn($roleMock);
        $this->rulesFactory->method('create')->willReturn($rulesMock);

        $this->adminRoles->execute([
            'adminroles' => [
                ['name' => 'Existing Role', 'resources' => ['Magento_Backend::all']],
            ],
        ]);
    }

    public function testExecuteLogsErrorWhenResourcesAreNull(): void
    {
        $existingRoleMock = $this->getMockBuilder(Role::class)
            ->disableOriginalConstructor()
            ->getMock();

        $collectionMock = $this->getMockBuilder(RoleCollection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addFieldToFilter', 'getSize', 'getFirstItem'])
            ->getMock();
        $collectionMock->method('addFieldToFilter')->willReturnSelf();
        $collectionMock->method('getSize')->willReturn(1);
        $collectionMock->method('getFirstItem')->willReturn($existingRoleMock);

        $roleMock = $this->getMockBuilder(Role::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCollection', 'save'])
            ->getMock();
        $roleMock->method('getCollection')->willReturn($collectionMock);

        $this->roleFactory->method('create')->willReturn($roleMock);

        $rulesMock = $this->getMockBuilder(Rules::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['saveRel'])
            ->getMock();
        $rulesMock->expects($this->never())->method('saveRel');
        $this->rulesFactory->method('create')->willReturn($rulesMock);

        $this->log->expects($this->once())->method('logError');

        $this->adminRoles->execute([
            'adminroles' => [
                ['name' => 'Test Role', 'resources' => null],
            ],
        ]);
    }
}
