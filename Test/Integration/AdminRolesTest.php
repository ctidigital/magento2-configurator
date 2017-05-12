<?php

namespace CtiDigital\Configurator\Model\Component;

use Magento\Authorization\Model\Role;
use Magento\TestFramework\Helper\Bootstrap;
use Symfony\Component\Yaml\Parser;

class AdminRolesTest extends \PHPUnit_Framework_TestCase
{
    const ROLE_NAME_KEY = "role_name";
    const TAX_MANAGER_TEST_ROLE_NAME = "Tax Manager";
    const ROLE_ID_KEY = "role_id";
    const ROLE_NAMES_DIFFERENT_MESSAGE = "Actual role name was different from expected role name";
    const RESOURCES_DIFFERENT_MESSAGE = "Actual resources were different to expected resources";
    const SALES_USERS_TEST_ROLE_NAME = "Sales Users";

    private $adminRolesYamlPath;

    private $expectedAllowedTaxManagerResources = array(
        'Magento_Backend::dashboard',
        'Magento_Backend::admin',
        'Magento_Sales::sales',
        'Magento_Sales::sales_operation',
        'Magento_Sales::sales_order',
        'Magento_Sales::actions',
        'Magento_Sales::create',
        'Magento_Sales::actions_view',
        'Magento_Sales::email',
        'Magento_Sales::reorder'
    );

    private $expectedAllowedSalesUsersResources = array(
        'Magento_Backend::dashboard',
        'Magento_Backend::admin',
        'Magento_Sales::sales',
        'Magento_Sales::create',
        'Magento_Sales::actions_view',
        'Magento_Sales::actions_edit',
        'Magento_Sales::cancel',
        'Magento_Sales::hold',
        'Magento_Sales::unhold'
    );

    /**
     * AdminRoles is the class under test
     *
     * @var AdminRoles
     */
    private $adminRolesComponent;

    /**
     * Role resource model
     *
     * @var Role
     */
    private $roleResourceModel;

    /**
     * Rules resource model
     *
     * @var
     */
    private $rulesResourceModel;

    public function setUp()
    {
        $this->adminRolesYamlPath = sprintf("%s/../../Samples/Components/AdminRoles/adminroles.yaml", __DIR__);
        $this->adminRolesComponent = Bootstrap::getObjectManager()
            ->get('CtiDigital\Configurator\Model\Component\AdminRoles');

        $roleFactory = Bootstrap::getObjectManager()
            ->get('Magento\Authorization\Model\RoleFactory');

        $rulesFactory = Bootstrap::getObjectManager()
            ->get('Magento\Authorization\Model\RulesFactory');

        $this->rulesResourceModel = $rulesFactory->create();
        $this->roleResourceModel = $roleFactory->create();
    }

    public function testShouldCreateNewAdminRolesFromYamlFile()
    {
        // given a yaml file containing AdminRoles
        $yamlParser = new Parser();
        $testAdminRoles = $yamlParser->parse(file_get_contents($this->adminRolesYamlPath), true);

        // when we run the AdminRoles component
        $this->adminRolesComponent->processData($testAdminRoles);

        // then it should generate new admin roles in the database, with associated resources
        $actualTaxManagerRole = $this->getRoleByName(self::TAX_MANAGER_TEST_ROLE_NAME);
        $this->assertNotEmpty($actualTaxManagerRole);

        $actualSalesUsersRole = $this->getRoleByName(self::SALES_USERS_TEST_ROLE_NAME);
        $this->assertNotEmpty($actualSalesUsersRole);

        $actualTaxManagerResources = $this->getResourceNamesFromRoleId($actualTaxManagerRole[self::ROLE_ID_KEY]);
        $this->assertResourceNamesArePresent(
            $this->expectedAllowedTaxManagerResources,
            $actualTaxManagerResources
        );

        $actualSalesUsersResources = $this->getResourceNamesFromRoleId($actualSalesUsersRole[self::ROLE_ID_KEY]);
        $this->assertResourceNamesArePresent(
            $this->expectedAllowedSalesUsersResources,
            $actualSalesUsersResources
        );
    }

    public function assertResourceNamesArePresent($expectedAllowedResources, $actualAllowedResources)
    {
        $this->assertEquals(
            sort($expectedAllowedResources),
            sort($actualAllowedResources),
            self::RESOURCES_DIFFERENT_MESSAGE
        );
    }
    private function getRoleByName($roleName)
    {
        $actualTaxManagerRole = $this->roleResourceModel->getCollection()
            ->addFieldToFilter(self::ROLE_NAME_KEY, $roleName)
            ->getFirstItem();
        return $actualTaxManagerRole;
    }

    private function getResourceNamesFromRoleId($roleId)
    {
        $resources = $this->rulesResourceModel->getCollection()
            ->addFieldToFilter(self::ROLE_ID_KEY, $roleId)
            ->addFieldToFilter("permission", "allow");
        return $this->extractResourceNames($resources->getData());
    }

    private function extractResourceNames(array $resources)
    {
        $resourceNames = array();
        foreach ($resources as $resource) {
            $resourceNames[] = $resource['resource_id'];
        }
        return $resourceNames;
    }
}
