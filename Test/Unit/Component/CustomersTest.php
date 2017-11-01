<?php
namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Component\Customers;
use CtiDigital\Configurator\Model\Exception\ComponentException;

/**
 * Class CustomersTest
 * @package CtiDigital\Configurator\Test\Unit\Component
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
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
        $this->setExpectedException(ComponentException::class);
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
        $this->setExpectedException(ComponentException::class, 'The column "email" is required.');
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

    /**
     * @dataProvider addressColumnProvider
     */
    public function testIsAddressColumn($column, $expected)
    {
        $this->assertEquals($expected, $this->component->getIsAddressColumn($column));
    }

    public function addressColumnProvider()
    {
        return [
            ['_address_fax', true],
            ['group_id', false],
            ['something_address_', false]
        ];
    }

    /**
     * @param $address
     * @param $expected
     *
     * @dataProvider addressValidProvider
     */
    public function testIsValidAddress($address, $expected)
    {
        $this->assertEquals($expected, $this->component->isAddressValid($address));
    }

    public function addressValidProvider()
    {
        return [
            [
                [
                    '_address_city' => 'Test',
                    '_address_country_id' => 'Test',
                    '_address_firstname' => 'Test',
                    '_address_lastname' => 'Test',
                    '_address_street' => 'Test',
                    '_address_telephone' => 'Test'
                ],
                true
            ],
            [
                [
                    '_address_city' => 'Test',
                    '_address_country_id' => '',
                    '_address_firstname' => '',
                    '_address_lastname' => '',
                    '_address_street' => '',
                    '_address_telephone' => ''
                ],
                false
            ],
            [
                [
                    '_address_city' => 'Test',
                    '_address_country_id' => 'Test',
                    '_address_firstname' => 'Test',
                    '_address_street' => 'Test',
                    '_address_telephone' => 'Test'
                ],
                false
            ],
            [
                [
                    '_address_city' => 'Test',
                    '_address_country_id' => 'Test',
                    '_address_firstname' => 'Test',
                    '_address_lastname' => 'Test',
                    '_address_street' => 'Test',
                    '_address_telephone' => ''
                ],
                false
            ],
        ];
    }

    public function testRemoveAddress()
    {
        $customer = [
            'email' => 'test',
            'firstname' => 'test',
            'lastname' => 'test',
            '_address_firstname' => 'test',
            '_address_lastname' => ''
        ];
        $test = [
            'email' => 'test',
            'firstname' => 'test',
            'lastname' => 'test'
        ];
        $this->assertEquals($test, $this->component->removeAddressFields($customer));
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
