<?php
namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Api\ComponentInterface;
use Magento\User\Model\UserFactory;
use Magento\Authorization\Model\RoleFactory;
use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Exception\ComponentException;

class AdminUsers implements ComponentInterface
{
    protected $alias = 'adminusers';
    protected $name = 'Admin Users';
    protected $description = 'Component to create Admin Users';

    /**
     * Factory class for user model
     *
     * @var UserFactory
     */
    protected $userFactory;

    /**
     * RoleFactory
     *
     * @var roleFactory
     */
    protected $roleFactory;

    /**
     * @var LoggerInterface
     */
    private $log;

    /**
     * AdminUsers constructor.
     * @param UserFactory $userFactory
     * @param RoleFactory $roleFactory
     * @param LoggerInterface $log
     */
    public function __construct(
        UserFactory $userFactory,
        RoleFactory $roleFactory,
        LoggerInterface $log
    ) {
        $this->userFactory = $userFactory;
        $this->roleFactory = $roleFactory;
        $this->log = $log;
    }

    /**
     * @param data
     */
    public function execute($data = null)
    {
        //Get Each Role
        foreach ($data['adminusers'] as $roleSet) {
            $roleName = $roleSet['rolename'];
            $roleId = $this->getUserRoleFromName($roleName);

            if ($roleId == null) {
                $this->log->logError(
                    sprintf('Admin Role "%s" does not exist', $roleName)
                );

                return;
            }

            //Run through users in this Role
            foreach ($roleSet['users'] as $userData) {
                $validData = $this->dataValidator($userData);
                try {
                    if (!$validData) {
                        return;
                    }

                    $this->createAdminUser($userData, $roleId);
                } catch (\Magento\Framework\Validator\Exception $e) {
                    $this->log->logError(sprintf('Magento Framework Validation Exception: %s', $e->getMessage()));
                } catch (ComponentException $e) {
                    $this->log->logError($e->getMessage());
                }
            }
        }
    }

    /**
     * Create new Admin User
     *
     * @param $userData
     * @param $roleId
     */
    private function createAdminUser($userData, $roleId)
    {
        $user = $this->userFactory->create();
        $userCount = $user->getCollection()->addFieldToFilter('email', $userData['email'])->getSize();

        if ($userCount > 0) {
            $this->log->logComment(
                sprintf(
                    'Admin User "%s" creation skipped: User with the email "%s" already exists',
                    $userData['firstname'] . ' ' . $userData['secondname'],
                    $userData['email']
                )
            );

            return;
        }

        $this->log->logInfo(
            sprintf(
                'Admin User "%s" being created',
                $userData['firstname'] . ' ' . $userData['secondname'] . ' :' . $userData['email']
            )
        );

        $user
            ->setUserName($userData['username'])
            ->setFirstName($userData['firstname'])
            ->setLastName($userData['secondname'])
            ->setEmail($userData['email'])
            ->setPassword($userData['password'])
            ->setIsActive(true)
            ->setRoleId($roleId);

        if ($user->validate()) {
            $user->save();

            $this->log->logInfo(
                sprintf('Admin User "%s" created successfully', $userData['firstname'] . ' ' . $userData['secondname'])
            );
        }
    }

    /**
     * Get ID of Role by Name
     *
     * @param $roleName
     * @return int|null
     */
    private function getUserRoleFromName($roleName)
    {
        $role = $this->roleFactory->create();
        $role = $role->getCollection()->addFieldToFilter('role_name', $roleName)->getFirstItem();

        return $role->getId();
    }

    /**
     *  Validate that required data is not empty
     *
     * @param $userData
     * @return bool
     */
    private function dataValidator($userData)
    {
        $params = ['username', 'firstname', 'secondname', 'email', 'password'];
        $invalidParams = [];

        //->save() will warn if incorrect email or password details, just need to ensure values exist
        foreach ($params as $param) {
            if (!isset($userData[$param]) && $userData[$param] == '') {
                $invalidParams[] = $userData[$param];
            }
        }

        if (!empty($invalidParams)) {
            $this->log->logError('Admin User data is missing: ' . implode(', ', $params));

            return false;
        }

        return true;
    }
}
