<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Api\ComponentInterface;
use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Exception\ComponentException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Tax\Model\Calculation\RuleFactory;
use Magento\Tax\Model\ClassModelFactory;
use Magento\Tax\Model\ResourceModel\Calculation\Rate\CollectionFactory;
use Magento\Tax\Model\ResourceModel\Calculation\Rule;

class TaxRules implements ComponentInterface
{
    protected string $alias = 'taxrules';
    protected string $name = 'Tax Rules';
    protected string $description = 'Component to create Tax Rules';

    /**
     * Defines Customer Tax Class string
     */
    const string TAX_CLASS_TYPE_CUSTOMER = 'CUSTOMER';

    /**
     * Defines Product Tax Class string
     */
    const string TAX_CLASS_TYPE_PRODUCT = 'PRODUCT';

    /**
     * TaxRules constructor.
     * @param CollectionFactory $taxRateFactory
     * @param ClassModelFactory $classModelFactory
     * @param RuleFactory $ruleFactory
     * @param Rule $ruleResource
     * @param LoggerInterface $log
     */
    public function __construct(
        protected readonly CollectionFactory $taxRateFactory,
        protected readonly ClassModelFactory $classModelFactory,
        protected readonly RuleFactory $ruleFactory,
        protected readonly Rule $ruleResource,
        protected readonly LoggerInterface $log
    ) {}

    /**
     * @throws LocalizedException
     */
    public function execute(mixed $data = null): void
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
            } catch (ComponentException $exception) {
                $this->log->logError($exception->getMessage());
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
     */
    public function getAttributesFromCsv($data = null): array
    {
        $attributes = [];
        foreach ($data as $attributeCode) {
            $attributes[] = $attributeCode;
        }
        return $attributes;
    }

    /**
     * Assign array values to useable keys names for rule creation
     */
    private function formatArray(array $taxRuleAttributes, array $rule): array
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
     * @throws LocalizedException
     */
    private function getRateIdsFromCode($rateNames = null): array
    {
        $rateIds = [];
        $rateNamesArray = explode(',', $rateNames);

        foreach ($rateNamesArray as $name) {
            $rateCollection = $this->taxRateFactory->create()
                ->addFieldToSelect('tax_calculation_rate_id');
            $rate = $rateCollection->addFieldToFilter('code', $name)->getFirstItem();
            $rateIds[] = $rate->getId();
        }

        return $rateIds;
    }

    /**
     * Use TaxClass name to get TaxClass Id
     *
     * @param null $names
     * @throws LocalizedException
     */
    private function taxClassIdsFromName(string $type, $names = null): array
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
                $classId = $classModel->getId();
            }

            $taxClassIds[] = $classId;
        }

        return $taxClassIds;
    }

    /**
     * Create TaxRule
     *
     * @throws LocalizedException
     */
    private function createTaxRule(array $ruleData): void
    {
        $rule = $this->ruleFactory->create();
        $ruleCount = $rule->getCollection()->addFieldToFilter('code', $ruleData['code'])->getSize();

        if ($ruleCount > 0) {
            $this->log->logComment(
                sprintf('Tax Rule "%s" already exists in database.', $ruleData['code'])
            );

            return;
        }

        $rule->setData($ruleData);

        $this->ruleResource->save($rule);

        $this->log->logInfo(
            sprintf('Tax Rule "%s" created.', $ruleData['code'])
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
