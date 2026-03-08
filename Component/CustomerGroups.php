<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Api\ComponentInterface;
use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Exception\ComponentException;
use Magento\Customer\Api\Data\GroupInterfaceFactory;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Tax\Model\ClassModelFactory;

class CustomerGroups implements ComponentInterface
{
    protected string $alias = 'customergroups';
    protected string $name = 'Customer Groups';
    protected string $description = 'Component to create Customer Groups';

    public function __construct(
        private readonly GroupRepositoryInterface $groupRepository,
        private readonly GroupInterfaceFactory $groupDataFactory,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        protected readonly ClassModelFactory $classModelFactory,
        private readonly LoggerInterface $log
    ) {
    }

    public function execute(mixed $data = null): void
    {
        foreach ($data['customergroups'] as $taxClass) {
            $taxClassName = $taxClass['taxclass'];
            $taxClassId = $this->getTaxClassIdFromName($taxClassName);

            if ($taxClassId) {
                foreach ($taxClass['groups'] as $group) {
                    try {
                        $this->validateGroupName($group);
                        $this->createCustomerGroup($group['name'], $taxClassId);
                    } catch (ComponentException $exception) {
                        $this->log->logError($exception->getMessage());
                    }
                }
            }
        }
    }

    /**
     * Create Customer Groups from YAML file.
     */
    private function createCustomerGroup(string $groupName, mixed $taxClassId): void
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('customer_group_code', $groupName)
            ->create();

        $groupList = $this->groupRepository->getList($searchCriteria);

        if ($groupList->getTotalCount() > 0) {
            $this->log->logInfo(
                sprintf('Customer Group "%s" already exists, creation skipped', $groupName)
            );

            return;
        }

        $group = $this->groupDataFactory->create();
        $group->setCode($groupName);
        $group->setTaxClassId((int) $taxClassId);
        $this->groupRepository->save($group);

        $this->log->logInfo(
            sprintf('Customer Group "%s" created', $groupName)
        );
    }

    /**
     * Perform customer group name validation.
     *
     * @throws ComponentException
     */
    private function validateGroupName(array $group): void
    {
        if (!isset($group['name'])) {
            throw new ComponentException((string)__('The customer group name is mandatory'));
        }

        if (strlen($group['name'])>32) {
            throw new ComponentException(
                (string)__('The customer group name "%1" is too long (maximum length is 32 characters)', $group['name'])
            );
        }
    }

    /**
     * Return tax class id when given name.
     */
    private function getTaxClassIdFromName(string $taxClassName): mixed
    {
        $taxClassModel = $this->classModelFactory->create();
        $taxClass = $taxClassModel->getCollection()->addFieldToFilter('class_name', $taxClassName)->getFirstItem();
        $taxclassId = $taxClass->getId();

        if (!$taxclassId) {
            $this->log->logError(
                sprintf('There is no Tax class with the name "%s" in this database', $taxClassName)
            );

            return null;
        }

        return $taxclassId;
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
