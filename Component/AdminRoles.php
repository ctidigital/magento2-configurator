<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Api\ComponentInterface;
use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Exception\ComponentException;
use Magento\Authorization\Model\Acl\Role\Group as RoleGroup;
use Magento\Authorization\Model\RoleFactory;
use Magento\Authorization\Model\RulesFactory;
use Magento\Authorization\Model\UserContextInterface;

/**
 * @SuppressWarnings(PHPMD.ShortVariable)
 */
class AdminRoles implements ComponentInterface
{
    protected string $alias = 'adminroles';
    protected string $name = 'Admin Roles';
    protected string $description = 'Component to create Admin Roles';

    /**
     * @var RoleFactory
     */
    protected RoleFactory $roleFactory;

    /**
     * @var RulesFactory
     */
    protected RulesFactory $rulesFactory;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $log;

    /**
     * AdminRoles constructor.
     */
    public function __construct(
        RoleFactory $roleFactory,
        RulesFactory $rulesFactory,
        LoggerInterface $log
    ) {
        $this->roleFactory = $roleFactory;
        $this->rulesFactory = $rulesFactory;
        $this->log = $log;
    }

    public function execute(mixed $data = null): void
    {
        if (isset($data['adminroles'])) {
            foreach ($data['adminroles'] as $role) {
                try {
                    if (isset($role['name'])) {
                        $this->createAdminRole($role['name'], $role['resources']);
                    }
                } catch (ComponentException $e) {
                    $this->log->logError($e->getMessage());
                }
            }
        }
    }

    /**
     * Create Admin user roles, or update them if they exist.
     */
    private function createAdminRole(string $roleName, mixed $resources): void
    {
        $role = $this->roleFactory->create();
        $roleCount = $role->getCollection()->addFieldToFilter('role_name', $roleName)->getSize();

        // Create or get existing user
        if ($roleCount > 0) {
            $this->log->logInfo(
                sprintf('Admin Role "%s" creation skipped: Already exists in database', $roleName)
            );

            //Get exisiting Role
            $role = $role->getCollection()->addFieldToFilter('role_name', $roleName)->getFirstItem();
            $this->setResourceIds($role, $resources);

            return;
        }

        $this->log->logInfo(
            sprintf('Admin Role "%s" being created', $roleName)
        );

        $role->setRoleName($roleName)
            ->setParentId(0)
            ->setRoleType(RoleGroup::ROLE_TYPE)
            ->setUserType(UserContextInterface::USER_TYPE_ADMIN)
            ->setSortOrder(0)
            ->save();

        $this->setResourceIds($role, $resources);
    }

    /**
     * Set ResourceIDs the Admin Role will have access to.
     */
    private function setResourceIds(mixed $role, ?array $resources): void
    {
        $roleName = $role->getRoleName();

        if ($resources !== null) {
            $this->log->logInfo(
                sprintf('Admin Role "%s" resources updating', $roleName)
            );

            $this->rulesFactory->create()->setRoleId($role->getId())->setResources($resources)->saveRel();
            return;
        }

        $this->log->logError(
            sprintf('Admin Role "%s" Resources are empty, please check your yaml file', $roleName)
        );
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
