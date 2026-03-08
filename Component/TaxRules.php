<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Api\ComponentInterface;
use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Exception\ComponentException;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Tax\Api\Data\TaxClassInterfaceFactory;
use Magento\Tax\Api\Data\TaxRuleInterfaceFactory;
use Magento\Tax\Api\TaxClassRepositoryInterface;
use Magento\Tax\Api\TaxRateRepositoryInterface;
use Magento\Tax\Api\TaxRuleRepositoryInterface;

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

    public function __construct(
        private readonly TaxRuleRepositoryInterface $taxRuleRepository,
        private readonly TaxRuleInterfaceFactory $taxRuleDataFactory,
        private readonly TaxRateRepositoryInterface $taxRateRepository,
        private readonly TaxClassRepositoryInterface $taxClassRepository,
        private readonly TaxClassInterfaceFactory $taxClassDataFactory,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly LoggerInterface $log
    ) {}

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
     * Assign array values to useable key names for rule creation
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
     * Use Rate code to get Rate ID via TaxRateRepositoryInterface.
     *
     * @param null $rateNames
     */
    private function getRateIdsFromCode($rateNames = null): array
    {
        $rateIds = [];

        foreach (explode(',', (string) $rateNames) as $name) {
            $criteria = $this->searchCriteriaBuilder
                ->addFilter('code', trim($name))
                ->create();

            foreach ($this->taxRateRepository->getList($criteria)->getItems() as $rate) {
                $rateIds[] = $rate->getId();
            }
        }

        return $rateIds;
    }

    /**
     * Use TaxClass name to get TaxClass Id via TaxClassRepositoryInterface.
     * Creates a new class if none is found with the given name and type.
     *
     * @param null $names
     */
    private function taxClassIdsFromName(string $type, $names = null): array
    {
        $taxClassIds = [];
        $normalizedType = strtoupper($type);

        foreach (explode(',', (string) $names) as $name) {
            $name = trim($name);
            $criteria = $this->searchCriteriaBuilder
                ->addFilter('class_name', $name)
                ->addFilter('class_type', $normalizedType)
                ->create();

            $items = $this->taxClassRepository->getList($criteria)->getItems();

            if (!empty($items)) {
                $taxClassIds[] = (int) reset($items)->getClassId();
                continue;
            }

            // Class does not exist yet — create it
            $taxClass = $this->taxClassDataFactory->create();
            $taxClass->setClassName($name)->setClassType($normalizedType);
            $saved = $this->taxClassRepository->save($taxClass);
            $taxClassIds[] = (int) $saved->getClassId();
        }

        return $taxClassIds;
    }

    /**
     * Create TaxRule via TaxRuleRepositoryInterface.
     * Skips if a rule with the same code already exists.
     */
    private function createTaxRule(array $ruleData): void
    {
        $criteria = $this->searchCriteriaBuilder
            ->addFilter('code', $ruleData['code'])
            ->create();

        if (!empty($this->taxRuleRepository->getList($criteria)->getItems())) {
            $this->log->logComment(
                sprintf('Tax Rule "%s" already exists in database.', $ruleData['code'])
            );

            return;
        }

        $rule = $this->taxRuleDataFactory->create();
        $rule->setCode($ruleData['code'])
             ->setPriority((int) ($ruleData['priority'] ?? 0))
             ->setPosition((int) ($ruleData['position'] ?? 0))
             ->setCalculateSubtotal((bool) ($ruleData['calculate_subtotal'] ?? false))
             ->setTaxRateIds($ruleData['tax_rate_ids'] ?? [])
             ->setCustomerTaxClassIds($ruleData['customer_tax_class_ids'] ?? [])
             ->setProductTaxClassIds($ruleData['product_tax_class_ids'] ?? []);

        $this->taxRuleRepository->save($rule);

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
