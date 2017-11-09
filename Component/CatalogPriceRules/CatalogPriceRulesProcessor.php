<?php
/**
 * @package  CtiDigital\Configurator
 * @author Bartosz Herba <b.herba@ctidigital.com>
 * @copyright 2017 CtiDigital
 */

namespace CtiDigital\Configurator\Component\CatalogPriceRules;

use CtiDigital\Configurator\Api\ComponentProcessorInterface;
use CtiDigital\Configurator\Api\LoggerInterface;
use Magento\CatalogRule\Api\CatalogRuleRepositoryInterface;
use Magento\CatalogRule\Api\Data\RuleInterfaceFactory;
use Magento\CatalogRule\Model\Rule;
use Magento\CatalogRule\Model\Rule\Job;

/**
 * Class CatalogPriceRulesProcessor
 */
class CatalogPriceRulesProcessor implements ComponentProcessorInterface
{
    /**
     * @var array
     */
    private $rules = [];

    /**
     * @var array
     */
    private $config = [];

    /**
     * @var RuleInterfaceFactory
     */
    private $ruleFactory;

    /**
     * @var CatalogRuleRepositoryInterface
     */
    private $catalogRuleRepository;

    /**
     * @var Job
     */
    private $ruleJob;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * CatalogPriceRules constructor.
     *
     * @param LoggerInterface $logger
     * @param RuleInterfaceFactory $ruleFactory
     * @param CatalogRuleRepositoryInterface $catalogRuleRepo
     * @param Job $ruleJob
     */
    public function __construct(
        LoggerInterface $logger,
        RuleInterfaceFactory $ruleFactory,
        CatalogRuleRepositoryInterface $catalogRuleRepo,
        Job $ruleJob
    ) {
        $this->logger = $logger;
        $this->ruleFactory = $ruleFactory;
        $this->catalogRuleRepository = $catalogRuleRepo;
        $this->ruleJob = $ruleJob;
    }

    /**
     * @param array $data
     *
     * @return $this
     */
    public function setData(array $data)
    {
        $this->rules = $data;

        return $this;
    }

    /**
     * @param array $config
     *
     * @return $this
     */
    public function setConfig(array $config)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Configure rules
     *
     * @return void
     */
    public function process()
    {
        $rulesCount = count($this->rules);
        $ite = 1;
        $this->logger->logInfo('Initializing configuration of Catalog Price Rules.');

        foreach ($this->rules as $ruleId => $ruleData) {
            $this->logger->logInfo("- Processing {$ruleId} [{$ite}/{$rulesCount}]...");

            /** @var Rule $rule */
            $rule = $this->ruleFactory->create();
            $this->fillRuleWithData($rule, $ruleData);

            try {
                $this->catalogRuleRepository->save($rule);
            } catch (\Exception $ex) {
                $this->logger->logError($ex->getMessage());
            }

            $ite++;
        }

        if ($this->isApplyAll()) {
            $this->logger->logInfo('- Applying all rules...');
            $this->ruleJob->applyAll();
        }

        $this->logger->logInfo('Catalog price rules configuration completed.');
    }

    /**
     * @param Rule $rule
     * @param array $ruleData
     *
     * @return void
     */
    private function fillRuleWithData(Rule $rule, array $ruleData)
    {
        $rule->setName($ruleData['name'] ?? "");
        $rule->setDescription($ruleData['description'] ?? "");
        $rule->setIsActive($ruleData['is_active'] ?? "");
        $rule->setSortOrder($ruleData['sort_order'] ?? "");
        $rule->setCustomerGroupIds($ruleData['customer_group_ids'] ?? "");
        $rule->setWebsiteIds($ruleData['website_ids'] ?? "");
        $rule->setFromDate($ruleData['from_date'] ?? "");
        $rule->setToDate($ruleData['to_date'] ?? "");
        $rule->setSimpleAction($ruleData['simple_action'] ?? "");
        $rule->setDiscountAmount($ruleData['discount_amount'] ?? "");
        $rule->setStopRulesProcessing($ruleData['stop_rules_processing'] ?? "");
        $rule->setConditionsSerialized($ruleData['conditions_serialized'] ?? "");
        $rule->setActionsSerialized($ruleData['actions_serialized'] ?? "");

        $rule->loadPost($rule->getData());
    }

    /**
     * Checks if should apply all rules
     *
     * @return bool
     */
    private function isApplyAll()
    {
        return isset($this->config['apply_all']) && true === $this->config['apply_all'];
    }
}
