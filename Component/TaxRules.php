<?php

namespace CtiDigital\Configurator\Component;

use Magento\Framework\ObjectManagerInterface;
use CtiDigital\Configurator\Model\LoggerInterface;
use Magento\Tax\Model\Calculation\RuleFactory;
use Magento\Tax\Model\Calculation\RateFactory;
use Magento\Tax\Model\ClassModelFactory;
use CtiDigital\Configurator\Model\Exception\ComponentException;

class TaxRules extends CsvComponentAbstract
{
    protected $alias = 'taxrules';
    protected $name = 'Tax Rules';
    protected $description = 'Component to create Tax Rules';

    /**
     * Defines Customer Tax Class string
     */
    const TAX_CLASS_TYPE_CUSTOMER = 'CUSTOMER';

    /**
     * Defines Product Tax Class string
     */
    const TAX_CLASS_TYPE_PRODUCT = 'PRODUCT';

    /**
     * @var RateFactory
     */
    protected $rateFactory;

    /**
     * @var RuleFactory
     */
    protected $ruleFactory;

    /**
     * @var ClassModelFactory
     */
    protected $classModelFactory;

    /**
     * TaxRules constructor.
     * @param LoggerInterface $log
     * @param ObjectManagerInterface $objectManager
     * @param RateFactory $rateFactory
     * @param ClassModelFactory $classModelFactory
     * @param RuleFactory $ruleFactory
     */
    public function __construct(
        LoggerInterface $log,
        ObjectManagerInterface $objectManager,
        RateFactory $rateFactory,
        ClassModelFactory $classModelFactory,
        RuleFactory $ruleFactory
    ) {
        parent::__construct($log, $objectManager);
        $this->rateFactory = $rateFactory;
        $this->classModelFactory = $classModelFactory;
        $this->ruleFactory = $ruleFactory;
    }

    /**
     * @param array|null $data
     */
    protected function processData($data = null)
    {
        //Check Row Data exists
        if (!isset($data[0])) {
            throw new ComponentException(
                sprintf('No row data found.')
            );
        }

        $taxRuleAttributes = $this->getAttributesFromCsv($data[0]);
        unset($data[0]);

        foreach ($data as $rule) {
            if (!isset($rule['0']) || $rule[0] == '') {
                $this->log->logError(
                    sprintf('Tax Rule creation skipped: Code is a required field')
                );

                continue;
            }

            $ruleData = $this->formatArray($taxRuleAttributes, $rule);

            try {
                $this->createTaxRule($ruleData);
            } catch (ComponentException $e) {
                $this->log->logError($e->getMessage());
            }

        }

        $this->log->logComment(
            sprintf('Tax Rules import finished')
        );
    }

    /**
     * Gets the first row of the CSV file as these should be the attribute keys
     *
     * @param null $data
     * @return array
     */
    public function getAttributesFromCsv ($data = null)
    {
        $attributes = [];
        foreach ($data as $attributeCode) {
            $attributes[] = $attributeCode;
        }
        return $attributes;
    }

    /**
     * Assign array values to useable keys names for rule creation
     *
     * @param array $taxRuleAttributes
     * @param array $rule
     * @return array
     */
    private function formatArray(array $taxRuleAttributes, array $rule)
    {
        $ruleData = [];

        //Set Keys
        foreach ($taxRuleAttributes as $column => $code) {
            $ruleData[$code] = $rule[$column];
        }

        //Ensure default values are passed
        foreach ($ruleData as $key => $value) {
            if (!isset($value)) {
                $ruleData[$key] = 0;
            }
        }

        $ruleData['tax_rate_ids'] = $this->getRateIdsFromCode($ruleData['tax_rate_ids']);

        //TODO if Tax ID not found, create it
        $ruleData['customer_tax_class_ids'] = $this->taxClassIdsFromName(
            self::TAX_CLASS_TYPE_CUSTOMER,
            $ruleData['customer_tax_class_ids']
        );

        $ruleData['product_tax_class_ids'] = $this->taxClassIdsFromName(
            self::TAX_CLASS_TYPE_PRODUCT,
            $ruleData['product_tax_class_ids']
        );

        return $ruleData;
    }

    /**
     * Use Rate code to get Rate ID
     *
     * @param null $rateNames
     * @return array
     */
    private function getRateIdsFromCode($rateNames = null)
    {
        $rateIds = [];
        $rateNamesArray = explode(',', $rateNames);

        $rateFactory = $this->rateFactory->create()->getCollection();
        foreach ($rateNamesArray as $name) {
            $rate = $rateFactory->addFieldToFilter('code', $name)->getFirstItem();

            $rateIds[] = $rate->getId();
        }

        return $rateIds;
    }

    /**
     * Use TaxClass name to get TaxClass Id
     *
     * @param $type
     * @param null $names
     * @return array
     */
    private function taxClassIdsFromName($type, $names = null)
    {
        $taxClassIds = [];
        $taxClassNamesArray = explode(',', $names);
        $classModel = $this->classModelFactory->create();
        $classCollection = $classModel->getCollection();

        foreach ($taxClassNamesArray as $name) {
            $class = $classCollection->addFieldToFilter('class_name', $name)->getFirstItem();
            $classId = $class->getId();

            if ($classId == 0) {
                $classModel->setClassName($name)
                    ->setClassType($type)
                    ->save();
            }

            $taxClassIds[] = $classId;
        }

        return $taxClassIds;
    }

    /**
     * Create TaxRule
     *
     * @param array $ruleData
     */
    private function createTaxRule(array $ruleData)
    {
        $rule = $this->ruleFactory->create();
        $ruleCount = $rule->getCollection()->addFieldToFilter('code', $ruleData['code'])->getSize();

        if ($ruleCount > 0) {

            $this->log->logComment(
                sprintf('Tax Rule "%s" already exists in database.', $ruleData['code'])
            );

            return;
        }

        $rule->setCode($ruleData['code'])
            ->setTaxRateIds($ruleData['tax_rate_ids'])
            ->setCustomerTaxClassIds($ruleData['customer_tax_class_ids'])
            ->setProductTaxClassIds($ruleData['product_tax_class_ids'])
            ->setPriority($ruleData['priority'])
            ->setCalculateSubtotal($ruleData['calculate_subtotal'])
            ->setPosition($ruleData['position'])
            ->save();

        $this->log->logInfo(
            sprintf('Tax Rule "%s" created.', $ruleData['code'])
        );


    }
}
