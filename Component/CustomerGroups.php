<?php
namespace CtiDigital\Configurator\Component;

use Symfony\Component\Yaml\Yaml;
use Magento\Customer\Model\GroupFactory;
use Magento\Tax\Model\ClassModelFactory;
use Magento\Framework\ObjectManagerInterface;
use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Model\Exception\ComponentException;

class CustomerGroups extends YamlComponentAbstract
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
     * AdminRoles constructor.
     * @param LoggerInterface $log
     * @param ObjectManagerInterface $objectManager
     * @param GroupFactory $groupFactory
     * @param ClassModelFactory $classModelFactory
     */
    public function __construct(
        LoggerInterface $log,
        ObjectManagerInterface $objectManager,
        GroupFactory $groupFactory,
        ClassModelFactory $classModelFactory
    ) {
        parent::__construct($log, $objectManager);

        $this->groupFactory = $groupFactory;
        $this->classModelFactory = $classModelFactory;
    }

    /**
     * @param $data
     */
    protected function processData($data = null)
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

        return;
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
}
