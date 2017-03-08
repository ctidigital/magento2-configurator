<?php
namespace CtiDigital\Configurator\Model\Component;

use Symfony\Component\Yaml\Yaml;
use Magento\Authorization\Model\RoleFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\Authorization\Model\RulesFactory;
use CtiDigital\Configurator\Model\LoggingInterface;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Authorization\Model\Acl\Role\Group as RoleGroup;
use CtiDigital\Configurator\Model\Exception\ComponentException;



class AdminRoles extends YamlComponentAbstract
{
    protected $alias = 'adminroles';
    protected $name = 'Admin Roless';
    protected $description = 'Component to create Admin Roless';

    /**
     * RoleFactory
     *
     * @var roleFactory
     */
    private $roleFactory;

    /**
     * RulesFactory
     *
     * @var rulesFactory
     */
    private $rulesFactory;

    /**
     * AdminRoles constructor.
     * @param LoggingInterface $log
     * @param ObjectManagerInterface $objectManager
     * @param RoleFactory $roleFactory
     * @param RulesFactory $rulesFactory
     */
    public function __construct(
        LoggingInterface $log,
        ObjectManagerInterface $objectManager,
        RoleFactory $roleFactory,
        RulesFactory $rulesFactory
    )
    {
        parent::__construct($log, $objectManager);

        $this->roleFactory = $roleFactory;
        $this->rulesFactory = $rulesFactory;
    }

    /**
     * @param array $data
     * @SuppressWarnings(PHPMD)
     */
    protected function processData($data = null)
    {

        if (isset($data['adminroles'])) {
            foreach ($data['adminroles'] as $role) {
                try {
                    if (isset($role['name'])) {
                        $role = $this->createAdminRole($role['name'], $role['resources']);
                    }
                } catch (ComponentException $e) {
                    $this->log->logError($e->getMessage());
                }
            }
        }
    }

    /**
     * Create Admin user roles, or update them if they exist
     *
     * @param $roleName
     * @param $resources
     */
    private function createAdminRole($roleName, $resources)
    {
        $role = $this->roleFactory->create();
        $roleCount = $role->getCollection()->addFieldToFilter('role_name', $roleName)->getSize();

        // Create or get existing user
        if ($roleCount > 0) {
            $this->log->logInfo(
                sprintf('Admin Role "%s" creation skipped: Already exists in database', $roleName)
            );
            $role = $role->getCollection()->addFieldToFilter('role_name', $roleName)->getFirstItem();

            //return;
        } else {
            $this->log->logInfo(
                sprintf('Admin Role "%s" being created', $roleName)
            );

            $role->setRoleName($roleName)
                ->setParentId(0)
                ->setRoleType(RoleGroup::ROLE_TYPE)
                ->setUserType(UserContextInterface::USER_TYPE_ADMIN)
                ->setSortOrder(0)
                ->save();
        }

        //Update viewable resources
        if ($resources !== null) {
            $this->log->logInfo(
                sprintf('Admin Role "%s" resources updating', $roleName)
            );

            $this->rulesFactory->create()->setRoleId($role->getId())->setResources($resources)->saveRel();
        } else {
            $this->log->logError(
                sprintf('Admin Role "%s" Resources are empty, please check your yaml file', $roleName)
            );
        }

    }

}