<?php
/**
 * @package  CtiDigital\Configurator
 * @author Bartosz Herba <b.herba@ctidigital.com>
 * @copyright 2017 CtiDigital
 */

namespace CtiDigital\Configurator\Test\Unit\Component\CatalogPriceRules;

use CtiDigital\Configurator\Component\CatalogPriceRules\CatalogPriceRulesProcessor;
use CtiDigital\Configurator\Model\Logging;
use Magento\CatalogRule\Model\CatalogRuleRepository;
use Magento\CatalogRule\Model\Rule;
use Magento\CatalogRule\Api\Data\RuleInterfaceFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Class CatalogPriceRulesTest
 * @codingStandardsIgnoreStart
 * @SuppressWarnings(PHPMD)
 */
class CatalogPriceRulesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CatalogPriceRulesProcessor
     */
    private $processor;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var Rule\Job|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mockJob;

    /**
     * @var CatalogRuleRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mockRuleRepository;

    /**
     * @var Rule|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mockRule;

    /**
     * @var RuleInterfaceFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mockRuleFactory;

    /**
     * @var Logging|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mockLogger;

    public function setUp()
    {
        $this->markTestSkipped('Test will be skipped until CI configuration will be fixed');

        $this->objectManager = new ObjectManager($this);

        $this->mockLogger = $this->getMockBuilder(Logging::class)
            ->disableOriginalConstructor()
            ->setMethods(['logInfo', 'logError'])
            ->getMock();

        $this->mockRuleFactory = $this->getMockBuilder(RuleInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->mockRule = $this->getMockBuilder(Rule::class)
            ->disableOriginalConstructor()
            ->setMethods(['getData'])
            ->getMock();

        $this->mockRuleRepository = $this->getMockBuilder(CatalogRuleRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['save'])
            ->getMock();

        $this->mockJob = $this->getMockBuilder(Rule\Job::class)
            ->disableOriginalConstructor()
            ->setMethods(['applyAll'])
            ->getMock();

        $this->processor = $this->objectManager->getObject(CatalogPriceRulesProcessor::class, [
            'logger'                => $this->mockLogger,
            'ruleFactory'           => $this->mockRuleFactory,
            'catalogRuleRepository' => $this->mockRuleRepository,
            'ruleJob'               => $this->mockJob,
        ]);
    }

    public function rulesDataProvider()
    {
        return [
            'rules' => [
                'rule1' => [
                    'name'                  => 'Test Rule',
                    'description'           => 'Some crafty description',
                    'is_active'             => 1,
                    'sort_order'            => 100,
                    'website_ids'           => [1],
                    'customer_group_ids'    => [1, 2],
                    'from_date'             => '',
                    'to_date'               => '11/10/2021',
                    'conditions_serialized' => 'a:7:{s:4:"type";s:48:"Magento\CatalogRule\Model\Rule\Condition\Combine";s:9:"attribute";N;s:8:"operator";N;s:5:"value";s:1:"1";s:18:"is_value_processed";N;s:10:"aggregator";s:3:"all";s:10:"conditions";a:1:{i:0;a:5:{s:4:"type";s:48:"Magento\CatalogRule\Model\Rule\Condition\Product";s:9:"attribute";s:3:"sku";s:8:"operator";s:2:"==";s:5:"value";s:5:"12asd";s:18:"is_value_processed";b:0;}}}',
                    'actions_serialized'    => 'a:4:{s:4:"type";s:48:"Magento\CatalogRule\Model\Rule\Action\Collection";s:9:"attribute";N;s:8:"operator";s:1:"=";s:5:"value";N;}',
                    'simple_action'         => 'by_fixed',
                    'discount_amount'       => 20,
                    'stop_rules_processing' => 0,
                ],
                'rule2' => [
                    'name'                  => 'Test Rule2',
                    'description'           => 'Some crafty description',
                    'is_active'             => 1,
                    'customer_group_ids'    => [1, 2],
                    'from_date'             => '',
                    'to_date'               => '11/10/2021',
                    'simple_action'         => 'by_fixed',
                    'discount_amount'       => 20,
                    'stop_rules_processing' => 0,
                ],
            ],
        ];
    }

    public function testProcessingEmptyRulesData()
    {
        $this->mockLogger->expects($this->at(0))
            ->method('logInfo')
            ->with('Initializing configuration of Catalog Price Rules.');

        $this->mockLogger->expects($this->at(1))
            ->method('logInfo')
            ->with('Catalog price rules configuration completed.');

        $this->processor->setData([])->process();
    }

    public function testValidRuleProcessing()
    {
        $it = 1;
        $rulesCount = count($this->rulesDataProvider()['rules']);
        foreach ($this->rulesDataProvider() as $rules) {
            $ruleId = key($rules);
            $this->mockLogger->expects($this->at(1))
                ->method('logInfo')
                ->with("- Processing {$ruleId} [{$it}/{$rulesCount}]...");

            $this->mockRule->expects($this->exactly($rulesCount))
                ->method('getData')
                ->willReturn([]);

            $this->mockRuleFactory->expects($this->exactly($rulesCount))
                ->method('create')
                ->willReturn($this->mockRule);

            $this->mockRuleRepository->expects($this->exactly($rulesCount))
                ->method('save');

            $this->processor->setData($rules)->setConfig([])->process();
            $it++;
        }
    }

    public function testProcessLogErrorWhenRuleSaveException()
    {
        $errMsg = __('some error msg');
        $this->mockRule->expects($this->atLeastOnce())
            ->method('getData')
            ->willReturn([]);

        $this->mockRuleFactory->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($this->mockRule);

        $this->mockRuleRepository->expects($this->atLeastOnce())
            ->method('save')
            ->willThrowException(new CouldNotSaveException($errMsg));

        $this->mockLogger->expects($this->atLeastOnce())
            ->method('logError')
            ->with($errMsg);

        $this->processor->setData($this->rulesDataProvider()['rules'])->setConfig([])->process();
    }

    public function testApplyingRules()
    {
        $this->mockLogger->expects($this->at(1))
            ->method('logInfo')
            ->with('- Applying all rules...');

        $this->mockJob->expects($this->once())
            ->method('applyAll');

        $this->processor->setData([])->setConfig(['apply_all' => true])->process();
    }
}
