<?php
namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Api\ComponentInterface;
use Magento\Customer\Model\GroupFactory;
use Magento\Tax\Model\ClassModelFactory;
use CtiDigital\Configurator\Exception\ComponentException;
use CtiDigital\Configurator\Api\LoggerInterface;

/**
 * Class CustomerGroups
 * @package CtiDigital\Configurator\Component
 * @SuppressWarnings(PHPMD.ShortVariable)
 */
class CustomerGroups implements ComponentInterface
{
    protected $alias = 'customergroups';
    protected $name = 'Customer Groups';
    protected $description = 'Component to create Customer Groups';

    /**
     * @var GroupFactory
     */
    private $groupFactory;

    /**
     * @var ClassModelFactory
     */
    protected $classModelFactory;

    /**
     * @var LoggerInterface
     */
    private $log;

    /**
     * AdminRoles constructor.
     * @param LoggerInterface $log
     * @param ObjectManagerInterface $objectManager
     * @param GroupFactory $groupFactory
     * @param ClassModelFactory $classModelFactory
     */
    public function __construct(
        GroupFactory $groupFactory,
        ClassModelFactory $classModelFactory,
        LoggerInterface $log
    ) {
        $this->groupFactory = $groupFactory;
        $this->classModelFactory = $classModelFactory;
        $this->log = $log;
    }

    /**
     * @param $data
     */
    public function execute($data = null)
    {
        foreach ($data['customergroups'] as $taxClass) {
            $taxClassName = $taxClass['taxclass'];
            $taxClassId = $this->getTaxClassIdFromName($taxClassName);

            if ($taxClassId) {
                foreach ($taxClass['groups'] as $group) {
                    try {
                        if (isset($group['name'])) {
                            $this->createCustomerGroup($group['name'], $taxClassId);
                        }
                    } catch (ComponentException $e) {
                        $this->log->logError($e->getMessage());
                    }
                }
            }
        }
    }

    /**
     * Create Customer Groups from YAML file
     *
     * @param string $groupName
     * @param int $taxClassId
     */
    private function createCustomerGroup($groupName, $taxClassId)
    {
        $customerGroup = $this->groupFactory->create();
        $groupCount = $customerGroup->getCollection()->addFieldToFilter('customer_group_code', $groupName)->getSize();

        if ($groupCount > 0) {
            $this->log->logInfo(
                sprintf('Customer Group "%s" already exists, creation skipped', $groupName)
            );

            return;
        }

        $customerGroup
            ->setCustomerGroupCode($groupName)
            ->setTaxClassId($taxClassId)
            ->save();

        $this->log->logInfo(
            sprintf('Customer Group "%s" created', $groupName)
        );
    }

    /**
     * Return tax class id when given name
     *
     * @param string $taxClassName
     * @return int|null
     */
    private function getTaxClassIdFromName($taxClassName)
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

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
}
