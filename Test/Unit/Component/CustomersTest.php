<?php
namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Component\Customers;
use CtiDigital\Configurator\Exception\ComponentException;
use FireGento\FastSimpleImport\Model\ImporterFactory;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Api\GroupManagementInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchResults;
use Magento\Indexer\Model\IndexerFactory;
use CtiDigital\Configurator\Api\LoggerInterface;
use Magento\Customer\Model\Data\Group;

class CustomersTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Customers
     */
    private $customers;

    /**
     * @var ImporterFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $importerFactory;

    /**
     * @var GroupRepositoryInterface | |PHPUnit_Framework_MockObject_MockObject
     */
    private $groupRepository;

    /**
     * @var GroupManagementInterface | \PHPUnit_Framework_MockObject_MockObject
     */
    private $groupManagement;

    /**
     * @var SearchCriteriaBuilder | \PHPUnit_Framework_MockObject_MockObject
     */
    private $searchBuilder;

    /**
     * @var SearchCriteria | \PHPUnit_Framework_MockObject_MockObject
     */
    private $searchCriteria;

    /**
     * @var SearchResults | \PHPUnit_Framework_MockObject_MockObject
     */
    private $searchResults;

    /**
     * @var IndexerFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $indexerFactory;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $log;

    protected function setUp()
    {
        $this->importerFactory = $this->getMockBuilder(ImporterFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchResults = $this->getMockBuilder(SearchResults::class)
            ->setMethods(['getItems'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->groupRepository = $this->getMockBuilder(GroupRepositoryInterface::class)
            ->setMethods(['save', 'getById', 'delete', 'deleteById', 'getList'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->groupRepository->expects($this->any())
            ->method('getList')
            ->willReturn($this->searchResults);

        $groupDefault = $this->createCustomerGroup(1);

        $this->groupManagement = $this->getMockBuilder(GroupManagementInterface::class)
            ->setMethods(
                ['isReadOnly', 'getNotLoggedInGroup', 'getLoggedInGroups', 'getAllCustomersGroup', 'getDefaultGroup']
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->groupManagement->expects($this->any())
            ->method('getDefaultGroup')
            ->willReturn($groupDefault);

        $this->searchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchBuilder->expects($this->any())
            ->method('create')
            ->willReturn($this->searchCriteria);

        $this->indexerFactory = $this->getMockBuilder(IndexerFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->log = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customers = new Customers(
            $this->importerFactory,
            $this->groupRepository,
            $this->groupManagement,
            $this->searchBuilder,
            $this->indexerFactory,
            $this->log
        );
    }

    public function testDataMissingRows()
    {
        $testData = [];
        $this->expectException(ComponentException::class);
        $this->customers->getColumnHeaders($testData);
    }

    public function testColumnsNotFound()
    {
        $testData = [['_website', '_store', 'firstname', 'notallowed']];
        $this->expectException(ComponentException::class, 'The column "email" is required.');
        $this->customers->getColumnHeaders($testData);
    }

    public function testGetColumns()
    {
        $expected = ['email', '_website', '_store', 'firstname', 'lastname'];
        $testData = [
            ['email', '_website', '_store', 'firstname', 'lastname'],
            ['example@example.com', 'base', 'Default', 'Test', 'Test']
        ];
        $this->customers->getColumnHeaders($testData);
        $this->assertEquals($expected, $this->customers->getHeaders());
    }

    public function testGroupIsValid()
    {
        $group1 = $this->createCustomerGroup(1);
        $group2 = $this->createCustomerGroup(2);
        $groups = [$group1, $group2];
        $this->searchResults->expects($this->any())
            ->method('getItems')
            ->willReturn($groups);
        $this->assertTrue($this->customers->isValidGroup(1));
    }

    public function testGroupNotValid()
    {
        $group1 = $this->createCustomerGroup(1);
        $group2 = $this->createCustomerGroup(2);
        $groups = [$group1, $group2];
        $this->searchResults->expects($this->any())
            ->method('getItems')
            ->willReturn($groups);
        $this->assertFalse($this->customers->isValidGroup(4));
    }

    public function testGetDefault()
    {
        $this->assertEquals(1, $this->customers->getDefaultGroupId());
    }

    private function createCustomerGroup($groupId)
    {
        $group = $this->getMockBuilder(Group::class)
            ->setMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMock();
        $group->expects($this->any())
            ->method('getId')
            ->willReturn($groupId);
        return $group;
    }
}
