<?php
namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Component\Customers;
use CtiDigital\Configurator\Exception\ComponentException;

class CustomersTest extends ComponentAbstractTestCase
{
    /**
     * @var \Magento\Customer\Model\ResourceModel\GroupRepository | |PHPUnit_Framework_MockObject_MockObject
     */
    private $groupRepository;

    /**
     * @var \Magento|Framework\Api\SearchResults | \PHPUnit_Framework_MockObject_MockObject
     */
    private $searchResults;

    /**
     * @var \Magento\Framework\Api\SearchCriteria | \PHPUnit_Framework_MockObject_MockObject
     */
    private $searchCriteria;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder | \PHPUnit_Framework_MockObject_MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var \Magento\Customer\Api\GroupManagementInterface | \PHPUnit_Framework_MockObject_MockObject
     */
    private $groupManagement;

    protected function componentSetUp()
    {
        $this->importerFactory = $this->getMockBuilder('FireGento\FastSimpleImport\Model\ImporterFactory')
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchResults = $this->getMockBuilder('Magento\Framework\Api\SearchResults')
            ->setMethods(['getItems'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->groupRepository = $this->getMockBuilder('Magento\Customer\Api\GroupRepositoryInterface')
            ->setMethods(['save', 'getById', 'delete', 'deleteById', 'getList'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->groupRepository->expects($this->any())
            ->method('getList')
            ->willReturn($this->searchResults);

        $this->searchCriteria = $this->getMockBuilder('Magento\Framework\Api\SearchCriteria')
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchCriteriaBuilder = $this->getMockBuilder('Magento\Framework\Api\SearchCriteriaBuilder')
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaBuilder->expects($this->any())
            ->method('create')
            ->willReturn($this->searchCriteria);

        $groupDefault = $this->createCustomerGroup(1);

        $this->groupManagement = $this->getMockBuilder('Magento\Customer\Api\GroupManagementInterface')
            ->setMethods(
                ['isReadOnly', 'getNotLoggedInGroup', 'getLoggedInGroups', 'getAllCustomersGroup', 'getDefaultGroup']
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->groupManagement->expects($this->any())
            ->method('getDefaultGroup')
            ->willReturn($groupDefault);

        $this->indexerFactory = $this->getMockBuilder('\Magento\Indexer\Model\IndexerFactory')
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->component = $this->testObjectManager->getObject(
            Customers::class,
            [
                'groupRepository' => $this->groupRepository,
                'groupManagement' => $this->groupManagement,
                'criteriaBuilder' => $this->searchCriteriaBuilder,
            ]
        );
        $this->className = Customers::class;
    }

    public function testDataMissingRows()
    {
        $testData = [];
        $this->expectException(ComponentException::class);
        $this->component->getColumnHeaders($testData);
    }

    public function testRequiredColumns()
    {
        $testData = [['email', '_website', '_store', 'firstname', 'lastname']];
        $this->component->getColumnHeaders($testData);
    }

    public function testColumnsNotFound()
    {
        $testData = [['_website', '_store', 'firstname', 'notallowed']];
        $this->expectException(ComponentException::class, 'The column "email" is required.');
        $this->component->getColumnHeaders($testData);
    }

    public function testGetColumns()
    {
        $expected = ['email', '_website', '_store', 'firstname', 'lastname'];
        $testData = [
            ['email', '_website', '_store', 'firstname', 'lastname'],
            ['example@example.com', 'base', 'Default', 'Test', 'Test']
        ];
        $this->component->getColumnHeaders($testData);
        $this->assertEquals($expected, $this->component->getHeaders());
    }

    public function testGroupIsValid()
    {
        $group1 = $this->createCustomerGroup(1);
        $group2 = $this->createCustomerGroup(2);
        $groups = [$group1, $group2];
        $this->searchResults->expects($this->any())
            ->method('getItems')
            ->willReturn($groups);
        $this->assertTrue($this->component->isValidGroup(1));
    }

    public function testGroupNotValid()
    {
        $group1 = $this->createCustomerGroup(1);
        $group2 = $this->createCustomerGroup(2);
        $groups = [$group1, $group2];
        $this->searchResults->expects($this->any())
            ->method('getItems')
            ->willReturn($groups);
        $this->assertFalse($this->component->isValidGroup(4));
    }

    public function testGetDefault()
    {
        $this->assertEquals(1, $this->component->getDefaultGroupId());
    }

    private function createCustomerGroup($groupId)
    {
        $group = $this->getMockBuilder('Magento\Customer\Model\Data\Group')
            ->setMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMock();
        $group->expects($this->any())
            ->method('getId')
            ->willReturn($groupId);
        return $group;
    }
}
